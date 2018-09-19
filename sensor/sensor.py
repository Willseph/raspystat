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
import subprocess
import sys
import time
import traceback

#####################################################################

# Constants
Path = os.path.dirname(os.path.abspath(__file__))+'/..'
ConfigFileName = Path+'/sensor/config.json'
LoopDelaySeconds = 5

# Error codes
ErrorExited = 'exited'
ErrorSensorError = 'sensor-error'

#####################################################################

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

#####################################################################

currentTemp = 1
verString = ''

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
		time.sleep (LoopDelaySeconds)
		print '----------'
		currentTemp = 1
		statusError = None
		
		currentTime = int (time.time ())
		print 'Current time: '+str(currentTime)
		
		# Reading from sensor
		try:
			print 'Reading temp...'
			currentTemp = ds18b20.readTemperature ()
			if currentTemp is None:
				print 'Could not read temperature from ds18b20 device. Ensure wiring is correct.'
			else:
				print 'Current temperature: '+str(currentTemp/1000.0)+' C'
		except:
			print 'Exception occurred reading temperature:'
			print traceback.format_exc ()
			print
		
		if ((not currentTemp) or currentTemp <= 1):
			print 'Error reading from sensor.'
			statusError = ErrorSensorError
		
		# Version info
		verBranch = subprocess.check_output (['git', '--git-dir', (Path+'/.git'), 'rev-parse', '--abbrev-ref', 'HEAD']).strip ()
		verClean = (subprocess.check_output (['git', '--git-dir', (Path+'/.git'), '--work-tree', Path, 'status']).strip ()).find ("working directory clean") > -1
		verCommit = subprocess.check_output (['git', '--git-dir', (Path+'/.git'), 'log', '-n', '1', '--pretty=format:%H']).strip ()
		verString = verBranch +','+ verCommit +','+ ('clean' if verClean else 'dirty')

		# Reporting to API
		status = {"temp":currentTemp, "error":statusError, "ver":verString}
		try:
			print 'Reporting status to server...'
			success = postToApi ('reportsensor', status, host, secret)
			if success is True:
				print 'Sensor updated'
			else:
				print 'Failed to update sensor on server'
		except:
			print 'Error posting to API:'
			print traceback.format_exc ()
			
		print '----------'
		print

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
status = {"temp":currentTemp, "error":ErrorExited, "ver":verString}
postToApi ('reportsensor', status, host, secret)
