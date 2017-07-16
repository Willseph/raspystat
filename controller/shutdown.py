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
import sys
import time

#####################################################################

# Relay boolean constants. Flipped for Sainsmart relay module.
BoardMode = gpio.BCM
ConfigFileName = os.path.dirname(os.path.abspath(__file__))+'/config.json'
RelayReverse = False

# Error codes
ErrorExited = 'exited'
ErrorMinMaxOverlap = 'min-max-overlap'

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

# Stopping controller service first
os.system ('sudo service raspystat-controller stop')

gpio.setwarnings (False)

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

fanPins = getSafely ('fanPins', config)
heatPins = getSafely ('heatPins', config)
coolPins = getSafely ('coolPins', config)
	
if (not fanPins):
	print 'Error: Fan pins undefined in '+ConfigFileName
	sys.exit (1)
	
if (not heatPins):
	print 'Error: Heat pins undefined in '+ConfigFileName
	sys.exit (1)
	
if (not coolPins):
	print 'Error: Cool pins undefined in '+ConfigFileName
	sys.exit (1)

RelayReverse = getSafely ('reverseRelays', config) is True
	
# Cleaning up GPIO
gpio.setmode (BoardMode)
allPins = list (set (fanPins) | set (heatPins) | set (coolPins))

for pin in allPins:
	gpio.setup(pin, gpio.OUT)

setPins (allPins, False)
gpio.cleanup()

print 'Attemping one last status update'
status = {"fan":0, "heat":0, "cool":0, "resting":0, "error":ErrorExited}
postToApi ('reportstatus', status, host, secret)

