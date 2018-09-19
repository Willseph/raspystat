# Raspystat

A distributed-sensor Raspberry Pi thermostat solution.

### *WARNING:*
***This project is incomplete. Before you start formatting your SD cards and cloning this project, keep in mind that some vital components are not yet here.*** 


## Introduction

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

This project is split into three components:

* Sensors
* HVAC controller
* Web server (LAMP)

A typical deployment of Raspystat should generally consist of one Raspberry Pi for the HVAC Controller, one Raspberry Pi for each sensor (of which you may have multiple within your home), and a machine capable of running a LAMP stack for the web server.

Using multiple sensors will allow you to toggle specific sensors within your home, effectively controlling which ones contribute, or do not contribute, to the overall average temperature within your home. However, only one sensor is required.

Additionally, it is entirely possible to deploy the HVAC controller and the web server on the same Pi if you so wish. You may also be able to integrate all three components into a single Pi, but that is not officially supported by this codebase.


## Prerequisites

Raspystat requires the following packages:

* Git
* Python 2.7

More to come soon


## Installation

### Web Server installation

Coming soon...ish


### Sensor Raspberry Pi installation

In order for your sensor Pi to sense the ambient temperature around it, it must be connected to a temperature sensor compoment. This project uses the inexpensive *DS18B20* component (about $2.50 USD each from [Amazon](https://www.amazon.com/Industry-Park-DS18B20-Thermometer-Temperature/dp/B01IVMJ1L2)), but the `sensor.py` file could be modified to use any sort of GPIO-connected sensor.

**[Click here to visit Adafruit's tutorial on how to connect the DS18B20 temperature sensor to a Raspberry Pi](https://learn.adafruit.com/adafruits-raspberry-pi-lesson-11-ds18b20-temperature-sensing/)**. You will not need any of the sample code from that tutorial for Raspystat, but the device must be visible via the `/sys/bus/w1` OneWire interface by adding this line to your Pi's `/boot/config.txt` file and rebooting the Pi:
```
dtoverlay=w1-gpio
```

This guide assumes your sensor Pis are running at least Raspbian Jessie Lite 4.9. Running another distro or version may cause the example commands below to behave unexpectedly.

It also assumes your primary admin user on the Pi is the default `pi` user. Make any adjustments necessary to the commands below if need be.

Ensure that you have updated your existing packages, and you have installed the necessary packages:
```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get install git python-pip
```

Install the necessary **pip** packages:
```
pip install requests
```

Clone this `Raspystat` repo into your home directory and enter the `sensor` subdirectory:
```
cd ~/
git clone https://github.com/Willseph/raspystat
cd raspystat/sensor
```

Make the necessary Python scripts executable:
```
sudo chmod +x sensor.py
sudo chmod +x shutdown.py
sudo chmod +x watchdog.py
```

Modify the `config.json.example` file with your preferred editor to set up the LAN address to the Raspystat server, as well as the **secret** for the sensor you are setting up (see the ***Web server*** section). Remove the hint lines as well.

Then, move the file to `config.json`:
```
mv config.json.example config.json
```

Move or copy the `raspystat-sensor.service` daemon unit file into your system's unit file directory, give it the right permissions, reload your unit files, enable, and finally start the service:
```
sudo mv raspystat-sensor.service /etc/systemd/system/raspystat-sensor.service
sudo chmod 664 /etc/systemd/system/raspystat-sensor.service
sudo systemctl daemon-reload
sudo systemctl enable raspystat-sensor.service
sudo systemctl start raspystat-sensor.service
```

After a few seconds, you should now see this sensor's temperature begin updating on the web app. If you don't, double-check that you have added the correct information in your `config.json` file.

If you are still having trouble, you may need to attempt to run the `sensor.py` script manually and use the output to debug the issue.

To set up the shutdown daemon, repeat the same step as before, but with the `raspystat-sensor-shutdown.service` daemon unit:
```
sudo mv raspystat-sensor-shutdown.service /etc/systemd/system/raspystat-sensor-shutdown.service
sudo chmod 664 /etc/systemd/system/raspystat-sensor-shutdown.service
sudo systemctl daemon-reload
sudo systemctl enable raspystat-sensor-shutdown.service
sudo systemctl start raspystat-sensor-shutdown.service
```

In order to set up the Watchdog which automatically reboots the Pi if something seems to be going wrong, you will need to edit your crontab:
```
crontab -e
```

Then, add the following line to set up the cron job to execute every two minutes:
```
*/2 * * * * sudo /usr/bin/python /home/pi/raspystat/sensor/watchdog.py
```

The Watchdog job should ensure that, in the event that a network hiccup or other kind of unforseen issue occurs which causes the `sensor.py` script to lock up or exit, the Pi will reboot and things should return to normal.

If you find yourself in a scenario where the Pi is constantly rebooting, making it difficult to keep an ssh session alive, you will need to quickly modify your crontab again and remove or comment the previous addition.


### HVAC controller Raspberry Pi installation

Coming soon...ish


## Authors

* **William Thomas** - *Primary author* - [Willseph](https://github.com/Willseph)

See also the list of [contributors](https://github.com/willseph/raspystat/contributors) who participated in this project.


## Built With

* [Bootstrap](https://getbootstrap.com/) - CSS framework used
* [IcoMoon](https://icomoon.io/) - Iconography


## License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE.md](LICENSE.md) file for details


## Acknowledgments

* Thanks to Adafruit for their DS18B20 tutorial provided above.


## Donate

If you wish to buy me a coffee or beer, you can do so easily with Square Cash!
<p align="center"><a href="https://cash.me/$willseph"><img src="https://i.imgur.com/cZMl8i0.png" alt="Donate" width="125" height="50"</a></p>
