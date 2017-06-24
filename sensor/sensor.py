#!/usr/bin/python

import ds18b20
temp = ds18b20.readTemperature()
if temp:
	print str(temp/1000.0*9.0/5.0 + 32)
else:
	print 'Could not read'
