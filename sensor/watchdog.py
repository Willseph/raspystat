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
import sys
import time
import traceback

#####################################################################

# Constants
Unit = "sensor"
ApiObj = "sensors"
Path = os.path.dirname(os.path.abspath(__file__))+'/..'
ConfigFileName = Path+'/'+Unit+'/config.json'
DeathTimeSeconds = 120
Verbose=False

#####################################################################

# Makes a GET call to the API
def getFromApi (call, obj, host, secret):
	url = host+'/call/'+call
	resp = requests.get(url, params={'secret':secret}).json()
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

# Sets a 5-second timer, then forces a shutdown signal
def rebootPi ():
	print 'Rebooting pi in 5 seconds...'
	time.sleep(5)
	print 'Current time: '+str(int (time.time ()))
	print 'Reboot'
	print '----------'
	print
	os.system('sudo shutdown -r now')
	sys.exit (0)

#####################################################################

needsToReboot = False
currentTime = -1

try:
	if Verbose:
		print '----------'
	currentTime = int (time.time ())
	if Verbose:
		print 'Current time: '+str(currentTime)

	# Loading config
	with open (ConfigFileName) as configFile:
		config = json.load (configFile)
	
	if not config:
		print 'Current time: '+str(currentTime)
		print 'Error loading configuration from '+ConfigFileName
		sys.exit (1)
	
	host = getSafely ('host', config)
	secret = getSafely ('secret', config)
	
	if not host:
		print 'Current time: '+str(currentTime)
		print 'Error: No host specified in '+ConfigFileName
		sys.exit (1)
	
	if not secret:
		print 'Current time: '+str(currentTime)
		print 'Error: No secret key specified in '+ConfigFileName
		sys.exit (1)
	
	# Fetching current status
	response = None
	try:
		response = getFromApi (ApiObj, ApiObj, host, secret)
	except:
		print
		print 'Error occurred getting response from API:'
		tb = traceback.format_exc ()
		print tb
		print
		needsToReboot = True
	
	if response:
		if len (response) < 1:
			print 'No response with provided secret found in API. Web may not be configured.'
			sys.exit (1)
		else:
			sensor = response[0]
			sensorTime = sensor['updated']
			if Verbose:
				print 'Last update time: '+str(sensorTime)
			difference = currentTime - sensorTime
			if Verbose:
				print 'Time difference: '+str(difference)+' second(s).'
			if difference >= DeathTimeSeconds:
				print 'Sensor has not been updated in longer than '+str(DeathTimeSeconds)+' seconds. This is cause for rebooting.'
				needsToReboot = True

except SystemExit:
	print
	print 'System exit'

except KeyboardInterrupt:
	print
	print 'Keyboard interrupt'

except:
	print
	print 'Current time: '+str(currentTime)
	print 'Error occurred:'
	tb = traceback.format_exc ()
	print tb

if needsToReboot:
	print 'Something is wrong. We need to reboot.'
	rebootPi ()
else:
	if Verbose:
		print 'Sensor status is valid. No need to reboot.'
		print '----------'
		print
