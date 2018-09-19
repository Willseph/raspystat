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
import subprocess
import sys
import time
import traceback

#####################################################################

# Constants
Path = os.path.dirname(os.path.abspath(__file__))+'/..'
ConfigFileName = Path+'/sensor/config.json'
DeathTimeSeconds = 120

# Error codes
ErrorSensorError = 'sensor-error'

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
	print 'Reboot'
	print '----------'
	print
	os.system('sudo shutdown -r now')
	sys.exit (0)

#####################################################################

needsToReboot = False

try:
	print '----------'
	currentTime = int (time.time ())
	print 'Current time: '+str(currentTime)

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
	
	# Fetching current status
	sensors = None
	try:
		sensors = getFromApi ('sensors', 'sensors', host, secret)
	except:
		print
		print 'Error occurred getting sensors from API:'
		tb = traceback.format_exc ()
		print tb
		print
		needsToReboot = True
	
	if sensors:
		if len (sensors) < 1:
			print 'No sensors with provided secret found in API. Web may not be configured.'
		else:
			sensor = sensors[0]
			sensorTime = sensor['updated']
			print 'Last update time: '+str(sensorTime)
			difference = currentTime - sensorTime
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
	print 'Error occurred:'
	tb = traceback.format_exc ()
	print tb

if needsToReboot:
	print 'Something is wrong. We need to reboot.'
	rebootPi ()
else:
	print 'Sensor status is valid. No need to reboot.'
	print '----------'
	print
