#!/usr/bin/python

# Raspystat
# Copyright (C) 2017  William Thomas
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

import json
import os
import requests
import RPi.GPIO as gpio;
import subprocess
import sys
import time
import traceback

#####################################################################

# Constants
Path = os.path.dirname(os.path.abspath(__file__))+'/..'
BoardMode = gpio.BCM
CompressorSafetyBufferSeconds = 60
ConfigFileName = Path+'/controller/config.json'
LoopDelaySeconds = 1
MaxAttempts = 20
RelayReverse = False
TemperatureChangeThreshold = 0.5

# Error codes
ErrorExited = 'exited'
ErrorMinMaxOverlap = 'min-max-overlap'

#####################################################################

# Attempts to GET an item from the API. After failing too many times, reboot is called
def attemptGetFromApi (call, obj, host, secret, maxAttempts):
	attempts = 0
	result = None
	while (result is None and attempts <= maxAttempts):
		try:
			result = getFromApi (call, obj, host, secret)
		except:
			pass

		if (not result):
			attempts += 1
			print 'Error getting '+obj+'. Attempt '+str(attempts)+'/'+str(maxAttempts)+'...'
			time.sleep (1)
			continue
	
	if (not result):
		print 'Could not GET '+obj+'. Internet may be offline or config may be wrong.'
		rebootPi ()
	
	return result

# Attempts to POST an item to the API. After failing too many times, reboot is called
def attemptPostToApi (call, data, host, secret, maxAttempts):
	attempts = 0
	result = None
	while (result is None and attempts <= maxAttempts):
		try:
			result = postToApi (call, data, host, secret)
		except:
			pass

		if (not result is True):
			attempts += 1
			print 'Error posting data to API. Attempt '+str(attempts)+'/'+str(maxAttempts)+'...'
			print '   - ' + str (result)
			print
			time.sleep (1)
			continue
	
	if (not result):
		print 'Could not POST. Internet may be offline or config may be wrong.'
		rebootPi ()
	
	return result

# Returns 1 if b is "truthy", otherwise returns 0
def boolInt (b):
	return 1 if b else 0

# Returns T/F based on whether or not the temperature is cold enough for heating to turn on
def coldEnoughForHeat (minTemp, currentTemp, heaterIsOn):
	global TemperatureChangeThreshold
	if heaterIsOn:
		return currentTemp < minTemp + TemperatureChangeThreshold
	else:
		return currentTemp <= minTemp - TemperatureChangeThreshold

# Makes a GET call to the API
def getFromApi (call, obj, host, secret):
	url = host+'/call/'+call
	resp = requests.get(url, params={'controller':secret}).json()
	if (getSafely ('success', resp) is True and not (getSafely (obj, resp) is None)):
		return resp[obj]
	print 'API call failed: '+str(resp)
	return None

# Safely gets a value from a dictionary by its key, or returns None on error
def getSafely (key, config):
	if (not key) or (not config):
		return None
	if key in config:
		return config[key]
	return None

# Returns T/F based on whether or not the temperature is hot enough for cooling to turn on
def hotEnoughForAir (maxTemp, currentTemp, coolingIsOn):
	global TemperatureChangeThreshold
	if coolingIsOn:
		return currentTemp > maxTemp - TemperatureChangeThreshold
	else:
		return currentTemp >= maxTemp + TemperatureChangeThreshold

# Makes a POST call to the API
def postToApi (call, data, host, secret):
	url = host + '/call/' + call
	data['secret'] = secret
	resp = requests.post (url, data = data).json ()
	if (getSafely ('success', resp)) is True:
		return True
	print 'API call failed: ' + str (resp)
	return False

# Sets a 5-second timer, then forces a shutdown signal
def rebootPi ():
	print 'Rebooting pi in 5 seconds...'
	time.sleep(5)
	os.system('sudo shutdown -r now')
	sys.exit (0)

# Enables or disables the provided pin
def setPin (pin, enabled):
	global RelayReverse
	relayOn = False if RelayReverse else True
	relayOff = (not relayOn)
	gpio.output (pin, (relayOn if enabled else relayOff))

# Enables or disables multiple pins in a given array
def setPins (pins, enabled):
	if enabled:
		print 'Setting pins: '+str(pins)
	else:
		print 'Unsetting pins: '+str(pins)

	for pin in pins:
		setPin (pin, enabled)

#####################################################################

allPins = []
gpio.setwarnings (False)
verString = ''

try:
	# Loading config
	with open (ConfigFileName) as configFile:
		config = json.load (configFile)
	
	if not config:
		print 'Error loading configuration from '+ConfigFileName
		sys.exit (1)
	
	RelayReverse = getSafely ('reverseRelays', config) is True
	
	fanPins = getSafely ('fanPins', config)
	heatPins = getSafely ('heatPins', config)
	coolPins = getSafely ('coolPins', config)
	
	if (not fanPins or len (fanPins) < 1):
		print 'Error: Fan pins undefined or empty in '+ConfigFileName
		sys.exit (1)
	
	if (not heatPins or len (heatPins) < 1):
		print 'Error: Heat pins undefined or empty in '+ConfigFileName
		sys.exit (1)
	
	if (not coolPins or len (coolPins) < 1):
		print 'Error: Cool pins undefined or empty in '+ConfigFileName
		sys.exit (1)
	
	host = getSafely ('host', config)
	secret = getSafely ('secret', config)
	
	if not host:
		print 'Error: No host specified in '+ConfigFileName
		sys.exit (1)
	
	if not secret:
		print 'Error: No secret key specified in '+ConfigFileName
		sys.exit (1)
	
	# Setting up GPIO
	gpio.cleanup ()
	gpio.setmode (BoardMode)
	
	allPins = list (set (fanPins) | set (heatPins) | set (coolPins))
	
	for pin in allPins:
		gpio.setup(pin, gpio.OUT)
	
	setPins (allPins, False)
	
	# Beginning main loop
	lastCompressorChange = 0
	
	fanOn = False
	heatOn = False
	coolOn = False
	while True:
		compressorRest = False
		statusError = None
		
		# Pulling sensors and settings from API
		settings = attemptGetFromApi ('settings', 'settings', host, secret, MaxAttempts)
		sensors = attemptGetFromApi ('sensors', 'sensors', host, secret, MaxAttempts)
		
		fanSettingEnabled = getSafely ('fan', settings) is True
		heatSettingEnabled = getSafely ('heat', settings) is True
		coolSettingEnabled = getSafely ('cool', settings) is True
		
		fanPreviouslyOn = fanOn
		coolPreviouslyOn = coolOn
		heatPreviouslyOn = heatOn

		# Finding the mean average temperature of all observed sensors
		sumTemperature = 0
		totalValidSensors = 0
		for sensor in sensors:
			if getSafely ('observed', sensor) is True and getSafely ('warning', sensor) is False:
				sumTemperature += getSafely ('temp', sensor)
				totalValidSensors += 1

		formattedTemp = '?'
		
		# If all sensors are offline or unobserved, just shut down HVAC
		if totalValidSensors < 1:
			print 'All sensors are either unobserved or in warning states.'
			coolOn = False
			heatOn = False
					
		else:
			tempC = (1.0 * sumTemperature / totalValidSensors) / 1000.0
			tempF = tempC*(9.0/5.0) + 32.0
			formattedTemp = round ((tempF if getSafely ('format', settings) == 'F' else tempC), 2)
			
			maxTempFormatted = round(getSafely ('max', settings) / 1000.0, 2)
			minTempFormatted = round(getSafely ('min', settings) / 1000.0, 2)
			
			coolOn = coolSettingEnabled and hotEnoughForAir (maxTempFormatted, formattedTemp, coolOn)
			heatOn = heatSettingEnabled and coldEnoughForHeat (minTempFormatted, formattedTemp, heatOn)
		
		currentTime = int (time.time ())
		
		# If min/max temperatures are overlapping, and heat/cool modes are both enabled, we have a problem
		if (coolOn and heatOn):
			coolOn = False
			heatOn = False
			fanOn = False
			
			statusError = ErrorMinMaxOverlap
		
		else:
			# Protecting HVAC system by enforcing a rest delay between compressor changes
			fanNeedsToStayOn = False
			if (currentTime < lastCompressorChange + CompressorSafetyBufferSeconds):
				compressorRest = True
				fanNeedsToStayOn = coolOn or heatOn
				coolOn = coolPreviouslyOn
				heatOn = heatPreviouslyOn
			
			if (coolOn):
				heatOn = False
		
			fanOn = fanSettingEnabled or coolOn or heatOn or fanNeedsToStayOn
			
			if (coolPreviouslyOn != coolOn or heatPreviouslyOn != heatOn):
				lastCompressorChange = currentTime
			
		print str(currentTime)		
		print str(formattedTemp)
		print 'Fan: '+str(fanOn)
		print 'Heat: '+str(heatOn)
		print 'Cool: '+str(coolOn)
		print 'Resting: '+str(compressorRest)
		print 'Error: '+str(statusError)

		# Enabling and disabling GPIO pins
		enabledPins = coolPins if coolOn else ( heatPins if heatOn else ( fanPins if fanOn else [] ) )
		disabledPins = list (set (allPins) - set (enabledPins))
		
		setPins (enabledPins, True)
		setPins (disabledPins, False)

		# Version info
		verBranch = subprocess.check_output (['git', '--git-dir', (Path+'/.git'), 'rev-parse', '--abbrev-ref', 'HEAD']).strip ()
		verClean = (subprocess.check_output (['git', '--git-dir', (Path+'/.git'), '--work-tree', Path, 'status']).strip ()).find ("working directory clean") > -1
		verCommit = subprocess.check_output (['git', '--git-dir', (Path+'/.git'), 'log', '-n', '1', '--pretty=format:%H']).strip ()
		verString = verBranch +','+ verCommit +','+ ('clean' if verClean else 'dirty')

		status = {"fan":boolInt (fanOn), "heat":boolInt (heatOn), "cool":boolInt (coolOn), "resting":boolInt (compressorRest), "error":statusError, "ver":verString}
		attemptPostToApi ('reportstatus', status, host, secret, MaxAttempts)
		print 'Status updated'
		print

		time.sleep (LoopDelaySeconds)

except SystemExit:
	print
	print 'System exit'

except KeyboardInterrupt:
	print
	print 'Keyboard interrupt'

except:
	print
	print 'Error occurred:'
	tb = traceback.format_exc ()
	print tb

print 'Shutting down GPIO...'
setPins (allPins, False)
gpio.cleanup ()

print 'Attemping one last status update'
status = {"fan":0, "heat":0, "cool":0, "resting":0, "error":ErrorExited, "ver":verString}
postToApi ('reportstatus', status, host, secret)
