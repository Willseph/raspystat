#!/usr/bin/python

# ThermoPi
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
from dotenv import Dotenv
import os
import requests
import subprocess
import sys
import time

def rebootPi():
	os.system('sudo shutdown -r now')

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

attemptsSinceLastSent = 0
maxAttempts = 30
pauseTime = 1

while True:
	env = getEnvSettings()
	secret = env['secret']
	url = env['host']+'/call/reportsensor'

	temp = ds18b20.readTemperature()
	if temp:
		print 'Temp: '+str(temp)
		try:
			resp = requests.post(url, data = {'secret':secret, 'temp':str(temp)}).json()
		except:
			print 'Could not report temperature.'
			attemptsSinceLastSent = attemptsSinceLastSent+1
			if(attemptsSinceLastSent > maxAttempts):
				print 'Internet may be out. Rebooting Pi...'
				time.sleep(1)
				rebootPi()
				break
			else:
				print 'Attempt '+str(attemptsSinceLastSent)+'/'+str(maxAttempts)+'...'
				time.sleep(2)
				continue
		attemptsSinceLastSent = 0
		print resp
	else:
		print 'Could not read temperature'

	time.sleep(pauseTime)
