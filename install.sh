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

MAIN_USER="pi"
RASPYSTAT_DIR="$HOME/raspystat"
CONTROLLER_UNIT="controller"
SENSOR_UNIT="sensor"

# Prints the usage of the program
function usage {
	echo "Usage: install.sh [-hcsud]"
	echo "  -c      Install as Raspystat controller"
	echo "  -s      Install as Raspystat sensor"
	echo "  -u      Uninstall without actually installing anything"
	echo "  -d      Allow dirty repo installations"
	echo "  -h      Display help"
	echo ""
	exit 1
}

# Begins the uninstall process for the provided unit name
function begin_uninstall {
	UNIT=$1
	UNIT_DIR="$RASPYSTAT_DIR/$UNIT"
	CONFIG="$UNIT_DIR/config.json"
	MAIN_DAEMON="raspystat-$UNIT"
	SHUTDOWN_DAEMON="raspystat-$UNIT-shutdown"

	echo "Running uninstall process for Raspystat $UNIT."

	echo "   Stopping daemons..."
	sudo service ${MAIN_DAEMON} stop
	sudo service ${SHUTDOWN_DAEMON} stop

	echo "   Disabling old daemons..."
	sudo systemctl disable ${MAIN_DAEMON}.service
	sudo systemctl disable ${SHUTDOWN_DAEMON}.service

	echo "   Deleting daemon unit files..."
	sudo rm /etc/systemd/system/${MAIN_DAEMON}.service -f
	sudo rm /etc/systemd/system/${SHUTDOWN_DAEMON}.service -f

	echo "   Reloading daemons..."
	sudo systemctl daemon-reload

	echo "   Removing cron job..."
	(crontab -l | grep -v "/raspystat/$UNIT/") | crontab -

	echo "   Raspystat $UNIT has been uninstalled."
	return 0
}

# Begins the installation process for the provided unit name
function begin_install {
	UNIT=$1
	UNIT_DIR="$RASPYSTAT_DIR/$UNIT"
	CONFIG="$UNIT_DIR/config.json"
	MAIN_DAEMON="raspystat-$UNIT"
	SHUTDOWN_DAEMON="raspystat-$UNIT-shutdown"
	CRONJOB_LOG="/var/log/raspystat-$UNIT-watchdog.log"
	CRONJOB="*/2 * * * * sudo /usr/bin/python $UNIT_DIR/watchdog.py >> $CRONJOB_LOG 2>&1"

	echo "Beginning installation process for Raspystat $UNIT."

	if [ ! -f ${CONFIG} ]
	then
		echo "   $UNIT directory has no config.json file. Cannot install."
		return 1
	fi

	echo "   Applying script permissions..."
	chmod +x ${UNIT_DIR}/${UNIT}.py
	chmod +x ${UNIT_DIR}/shutdown.py
	chmod +x ${UNIT_DIR}/watchdog.py

	echo "   Adding daemon unit files..."
	sudo cp ${UNIT_DIR}/${MAIN_DAEMON}.service /etc/systemd/system/${MAIN_DAEMON}.service
	sudo cp ${UNIT_DIR}/${SHUTDOWN_DAEMON}.service /etc/systemd/system/${SHUTDOWN_DAEMON}.service
	sudo chmod 664 /etc/systemd/system/${MAIN_DAEMON}.service
	sudo chmod 664 /etc/systemd/system/${SHUTDOWN_DAEMON}.service

	echo "   Reloading daemons..."
	sudo systemctl daemon-reload

	echo "   Re-enabling daemons..."
	sudo systemctl enable ${MAIN_DAEMON}.service
	sudo systemctl enable ${SHUTDOWN_DAEMON}.service

	echo "   Restarting daemons..."
	sudo service ${MAIN_DAEMON} start
	sudo service ${SHUTDOWN_DAEMON} start

	echo "   Creating log file..."
	sudo touch ${CRONJOB_LOG}
	sudo chown ${MAIN_USER}:${MAIN_USER} ${CRONJOB_LOG}

	echo "   Adding cron job..."
	((crontab -l | grep -v "$UNIT_DIR/") ; echo "$CRONJOB") | crontab -

	echo "   Raspystat $UNIT has been installed!"
	return 0
}

# Root check
if [ $EUID -eq 0 ]
then
	echo "Do not run with sudo." 1>&2
	exit 1
fi
if [ $(sudo -n -l 2>&1 | egrep -c -i "not allowed to run sudo|unknown user") != 0 ]
then
	echo "Current user does not have sudo access."
	exit 1
fi

# Setting arg flags
INSTALL_CONTROLLER=0
INSTALL_SENSOR=0
UNINSTALL_ONLY=0
ALLOW_DIRTY=0
while getopts 'csudh' flag; do
	case "${flag}" in
		c) INSTALL_CONTROLLER=1 ;;
		s) INSTALL_SENSOR=1 ;;
		u) UNINSTALL_ONLY=1 ;;
		d) ALLOW_DIRTY=1 ;;
		h) usage ;;
	esac
done

# Checking for unit flags
if [ $INSTALL_CONTROLLER -eq 0 ] && [ $INSTALL_SENSOR -eq 0 ] && [ $UNINSTALL_ONLY -eq 0 ]
then
	echo "Invalid usage. Must specify installation as either controller or sensor unit."
	usage
fi
if [ $INSTALL_CONTROLLER != 0 ] && [ $INSTALL_SENSOR != 0 ]
then
	echo "Invalid usage. Cannot simultaneously install as both controller and sensor unit."
	usage
fi

echo "Moving to Raspystat directory."
cd ${RASPYSTAT_DIR}

# Uninstalling
begin_uninstall "$CONTROLLER_UNIT"
begin_uninstall "$SENSOR_UNIT"

# Install unit if uninstall-only flag is not set
if [ $UNINSTALL_ONLY -eq 0 ]
then
	# Checking git status
	git update-index -q --refresh
	CHANGED=$(git diff-index --name-only HEAD --)

	if [ -n "$CHANGED" ]
	then
		echo ""
		echo "*** WARNING: Your working directory is not clean."

		# Cancel because dirty installs are not allowed
		if [ $ALLOW_DIRTY -eq 0 ]
		then
			echo "Cancelling installation. See usage to allow dirty installations."
			usage
		fi
	fi

	# Installing as controller
	if [ $INSTALL_CONTROLLER != 0 ]
	then
		echo ""
		begin_install "$CONTROLLER_UNIT"
	fi

	# Installing as sensor
	if [ $INSTALL_SENSOR != 0 ]
	then
		echo ""
		begin_install "$SENSOR_UNIT"
	fi
fi

echo ""
exit 0
