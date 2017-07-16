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
if [[ $EUID -eq 0 ]]; then
	echo "Do not run with sudo" 1>&2
	exit 1
fi

UNIT="controller"
RASPYSTAT_DIR="$HOME/Raspystat"
UNIT_DIR="$RASPYSTAT_DIR/$UNIT"
CONFIG="$UNIT_DIR/config.json"
MAIN_DAEMON="raspystat-$UNIT"
SHUTDOWN_DAEMON="raspystat-$UNIT-shutdown"

if [[ ! -f ${CONFIG} ]]; then
	echo "$UNIT has not been configured."
	exit 1
fi

echo "Moving to Raspystat directory and pulling latest code from master"
cd ${RASPYSTAT_DIR}
git checkout master
git pull origin
echo ""

echo "Stopping daemons"
sudo service ${MAIN_DAEMON} stop
sudo service ${SHUTDOWN_DAEMON} stop
echo ""

echo "Disabling old daemons"
sudo systemctl disable ${MAIN_DAEMON}.service
sudo systemctl disable ${SHUTDOWN_DAEMON}.service
echo ""

echo "Replacing daemons"
sudo cp ${UNIT_DIR}/${MAIN_DAEMON}.service /etc/systemd/system/${MAIN_DAEMON}.service
sudo cp ${UNIT_DIR}/${SHUTDOWN_DAEMON}.service /etc/systemd/system/${SHUTDOWN_DAEMON}.service
echo ""

echo "Reloading daemons"
sudo systemctl daemon-reload
echo ""

echo "Re-enabling daemons"
sudo systemctl enable ${MAIN_DAEMON}.service
sudo systemctl enable ${SHUTDOWN_DAEMON}.service
echo ""

echo "Restarting daemons"
sudo service ${MAIN_DAEMON} start
sudo service ${SHUTDOWN_DAEMON} start
echo ""

COMMIT=$(eval git log --pretty=format:'%h' -n 1)

echo "Raspystat $UNIT has been updated to the latest version ($COMMIT)"
echo "Rebooting is not necessary, but recommended"
echo ""
