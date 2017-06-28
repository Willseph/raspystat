# ThermoPi

A distributed-sensor Raspberry Pi thermostat solution.


## Introduction

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

This project is split into three components:

* Sensors
* HVAC controller
* Web server (LAMP)

A typical deployment of ThermoPi should generally consist of one Raspberry Pi for the HVAC Controller, one Raspberry Pi for each sensor (of which you may have multiple within your home), and a machine capable of running a LAMP stack for the web server.

Using multiple sensors will allow you to toggle specific sensors within your home, effectively controlling which ones contribute, or do not contribute, to the overall average temperature within your home. However, only one sensor is required.

Additionally, it is entirely possible to deploy the HVAC controller and the web server on the same Pi if you so wish. You may also be able to integrate all three components into a single Pi, but that is not officially supported by this codebase.


## Prerequisites

ThermoPi requires the following packages:

* Git
* Python 2.7

More to come soon


## Installing

#### Web Server

Coming soon


#### Sensor Raspberry Pi

In order for your sensor Pi to sense the ambient temperature around it, it must be connected to a temperature sensor compoment. This project uses the inexpensive *DS18B20* component (about $2.50 USD each from [Amazon](https://www.amazon.com/Industry-Park-DS18B20-Thermometer-Temperature/dp/B01IVMJ1L2)), but the `sensor.py` file could be modified to use any sort of GPIO-connected sensor.

**[Click here to visit Adafruit's tutorial on how to connect the DS18B20 temperature sensor to a Raspberry Pi](https://learn.adafruit.com/adafruits-raspberry-pi-lesson-11-ds18b20-temperature-sensing/)**. You will not need any of the sample code from that tutorial for ThermoPi, but the device must be visible via the `/sys/bus/w1` OneWire interface by adding this line to your Pi's `/boot/config.txt` file and rebooting the Pi:

```
dtoverlay=w1-gpio
```

This guide assumes your sensor Pis are running at least Raspbian Jessie Lite 4.9. Running another distro or version may cause the example commands below to behave unexpectedly.

It also assumes your primary admin user on the Pi is the default `pi` user. Make any adjustments necessary to the commands below if need be.

Clone this `ThermoPi` repo into your home directory and enter the `sensor` subdirectory:

```
cd ~/
git clone git@github.com:Willseph/ThermoPi.git
cd ThermoPi/sensor
```

Make the `sensor.py` script executable:

```
sudo chmod +x sensor.py
```

Move or copy the `thermopi-sensor.service` daemon unit file into your system's unit file directory, give it the right permissions, reload your unit files, enable, and finally start the service:

```
sudo mv thermopi-sensor.service /etc/systemd/system/thermopi-sensor.service
sudo chmod 664 /etc/systemd/system/thermopi-sensor.service
sudo systemctl daemon-reload
sudo systemctl enable thermopi-sensor.service
sudo systemctl start thermopi-sensor.service
```

After a few seconds, you should now see this sensor's temperature begin updating on the web app. If you don't, double-check that you have added the correct information in your `.env` file.

If you are still having trouble, you may need to attempt to run the `sensor.py` script manually and use the output to debug the issue.


#### HVAC Controller Raspberry Pi

Coming soon


## Authors

* **William Thomas** - *Primary author* - [Willseph](https://github.com/Willseph)

See also the list of [contributors](https://github.com/Willseph/ThermoPi/contributors) who participated in this project.


## Built With

* [Bootstrap](https://getbootstrap.com/) - CSS framework used
* [IcoMoon](https://icomoon.io/) - Iconography


## License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE.md](LICENSE.md) file for details


## Acknowledgments

* Thanks to Adafruit for their DS18B20 tutorial provided above.

sudo mv thermopi-sensor.service /etc/systemd/system/thermopi-sensor.service
pi@thermopi-2:~/ThermoPi/sensor $ sudo chmod 664 /etc/systemd/system/thermopi-sensor.service
pi@thermopi-2:~/ThermoPi/sensor $ sudo systemctl daemon-reload
pi@thermopi-2:~/ThermoPi/sensor $ sudo systemctl enable thermopi-sensor.service
Created symlink from /etc/systemd/system/default.target.wants/thermopi-sensor.service to /etc/systemd/system/thermopi-sensor.service.
pi@thermopi-2:~/ThermoPi/sensor $ sudo systemctl start thermopi-sensor.service
## Donate

If you wish to buy me a coffee or beer, you can do so easily with Squarecash!
<p align="center"><a href="https://cash.me/$willseph"><img src="https://i.imgur.com/cZMl8i0.png" alt="Donate" width="125" height="50"</a></p>
