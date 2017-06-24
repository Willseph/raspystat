#!/usr/bin/python

import ds18b20
temp = ds18b20.readTemperature()
if temp:
	print temp
else:
	print 'Could not read'
