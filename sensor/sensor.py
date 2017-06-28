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
