# ThermoPi

A distributed-sensor Raspberry Pi thermostat solution

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

This README assumes your sensor Pis are running at least Raspbian Jessie Lite 4.9. Running another distro or version may cause the example commands below to behave unexpectedly.

It also assumes your primary admin user on the Pi is the default `pi` user. Make any adjustments necessary to the commands below if need be.

Clone this `ThermoPi` repo into your home directory and enter it:

```
cd ~/
git clone git@github.com:Willseph/ThermoPi.git
cd ThermoPi
```

More to come soon

#### HVAC Controller Raspberry Pi

Coming soon

## Authors

* **William Thomas** - *Primary author* - [Willseph](https://github.com/Willseph)

See also the list of [contributors](https://github.com/Willseph/ThermoPi/contributors) who participated in this project.

## License

This project is licensed under the **GNU General Public License v3.0** - see the [LICENSE.md](LICENSE.md) file for details
