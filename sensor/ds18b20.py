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

import os
import glob
import time

def read_temp_raw():
	os.system('sudo modprobe w1-gpio')
	os.system('sudo modprobe w1-therm')
	base_dir = '/sys/bus/w1/devices/'
	device_file = None
	device_folders = glob.glob(base_dir + '28*')

	if len(device_folders) > 0:
		device_folder = device_folders[0]
		device_file = device_folder + '/w1_slave'

	if (not device_file):
		return None
	f = open(device_file, 'r')
	lines = f.readlines()
	f.close()
	return lines

def readTemperature():
	lines = read_temp_raw()
	if (not lines):
		return None
	if len(lines) >= 2 and 'YES' in lines[0] and 't=' in lines[1]:
		return int(lines[1].split('t=', 1)[1].strip())
	return None
