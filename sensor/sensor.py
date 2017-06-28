#!/usr/bin/python

import ds18b20
from dotenv import Dotenv
import os
import requests
import subprocess
import sys
import time

def getEnvSettings():
	try:
		path = os.path.dirname(os.path.abspath(__file__))
		dotenv = Dotenv(path+'/.env')
	except IOError:
		print 'Could not locate or open .env file.'
		sys.exit(1)
	env = {}
	
	if not ("SECRET" in dotenv):
        	print 'Error, field "SECRET" not found in .env'
        	sys.exit(1)
	env['secret'] = dotenv['SECRET'].strip()

	if not ("HOST" in dotenv):
        	print 'Error, field "HOST" not found in .env'
        	sys.exit(1)
	env['host'] = dotenv['HOST'].strip()

	return env

#####################################################################

env = getEnvSettings()
print env
print ''

pauseTime = 5
secret = env['secret']
url = env['host']+'/call/reportsensor'

while True:
	temp = str(ds18b20.readTemperature())
	print 'Temp: '+temp
	if temp:
		resp = requests.post(url, data = {'secret':secret, 'temp':temp}).json()
		print resp

	print ''
	time.sleep(pauseTime)
