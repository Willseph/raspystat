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

import ds18b20
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
ConfigFileName = os.path.dirname(os.path.abspath(__file__))+'/config.json'
LoopDelaySeconds = 1
MaxAttempts = 20
ReadTempAttempts = 5

# Error codes
ErrorExited = 'exited'
ErrorSensorError = 'sensor-error'

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

# Attempts to read the temperature from the ds18b20 sensor, and returns None if cannot
def attemptReadTemp (maxAttempts):
	readAttempts = 0
	temp = None

	while readAttempts < maxAttempts:
		try:
			print 'Reading temp...'
			temp = ds18b20.readTemperature ()

		except KeyboardInterrupt:
			return False

		except SystemExit:
			return False

		except:
			pass

		if ((not temp) or temp <= 1):
			readAttempts += 1
			print 'Could not read temp. Attempt ' + str (readAttempts) + '/' + str (maxAttempts) + '...'
			print
		else:
			return temp
	
	return None

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

#####################################################################

currentTemp = 1

try:
	# Loading config
	with open (ConfigFileName) as configFile:
		config = json.load (configFile)
	
	if not config:
		print 'Error loading configuration from '+ConfigFileName
		sys.exit (1)
	
	host = getSafely ('host', config)
	secret = getSafely ('secret', config)
	
	if not host:
		print 'Error: No host specified in '+ConfigFileName
		sys.exit (1)
	
	if not secret:
		print 'Error: No secret key specified in '+ConfigFileName
		sys.exit (1)
	
	# Beginning main loop
	while True:
		currentTemp = 1
		statusError = None
		
		currentTime = int (time.time ())
		print str(currentTime)
		
		# Reading from sensor
		currentTemp = attemptReadTemp (ReadTempAttempts)
		
		# Hacky, don't look!
		if (currentTemp is False):
			break
		
		if ((not currentTemp) or currentTemp <= 1):
			print 'Error reading from sensor.'
			statusError = ErrorSensorError
		else:
			print 'Temp: ' + str (currentTemp)
		
		# Reporting to API
		status = {"temp":currentTemp, "error":statusError}
		attemptPostToApi ('reportsensor', status, host, secret, MaxAttempts)
		print 'Sensor updated'
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

print 'Attemping one last status update'
status = {"temp":currentTemp, "error":ErrorExited}
postToApi ('reportsensor', status, host, secret)
