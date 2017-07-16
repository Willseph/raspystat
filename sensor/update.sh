#!/usr/bin/env bash

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

# Root check
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 1>&2
	exit 1
fi

if [[ ! -f ~/Raspystat/sensor/config.jsons ]]; then
	echo "Sensor has not been configured."
	exit 1
fi

# Stopping daemons
sudo service raspystat-sensor stop
sudo service raspystat-sensor-shutdown stop

# Moving to Raspystat directory and pulling latest code from master
cd ~/Raspystat
git checkout master
git pull origin

# Disabling old daemons
sudo systemctl disable raspystat-sensor.service
sudo systemctl disable raspystat-sensor-shutdown.service

# Replacing daemons
sudo cp ~/Raspystat/sensor/raspystat-sensor.service /etc/systemd/system/raspystat-sensor.service
sudo cp ~/Raspystat/sensor/raspystat-sensor-shutdown.service /etc/systemd/system/raspystat-sensor-shutdown.service

# Reloading daemons
sudo systemctl daemon-reload

# Re-enabling daemons
sudo systemctl enable raspystat-sensor.service
sudo systemctl enable raspystat-sensor-shutdown.service

# Restarting pi
echo 'Restarting pi...'
sudo reboot now
