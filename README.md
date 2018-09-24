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

It also assumes your primary admin users on the Pis are the default `pi` user. Make any adjustments necessary to the commands below if need be.

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
git clone https://github.com/willseph/raspystat
cd raspystat/sensor
```

Copy the `config.json.example` file to `config.json`:
```
cp config.json.example config.json
```

Modify the `config.json` file with your preferred editor to set up the LAN address to the Raspystat server, as well as the **secret** for the sensor you are setting up (see the ***Web server*** section). Remove the hint lines as well.

Finally, run the installation script with the `-s` flag (indicating that you are installing as a sensor unit):
```
cd ~/raspystat
./install.sh -s
```

The script will automatically set up all of the necessary services and jobs to ensure the sensor stays alive, or in an unrecoverable state, reboots the Pi.

If you find yourself in a scenario where the Pi is constantly rebooting, making it difficult to keep an ssh session alive, you will need to quickly modify your crontab and remove or comment the watchdog job. From that point, you should be able to debug any issues by manually running the `sensor.py` script in the `raspystat/sensor` directory and checking the output.


### HVAC controller Raspberry Pi installation

In order for your controller Pi to control the HVAC unit in your home, you need to physically wire the Pi up to the wires that control the HVAC unit. This project accomplishes this by using a four-channel relay module, which is relatively cheap, and takes the guesswork out of voltages and current when it comes to your HVAC interface.

##### TODO: Add GPIO wiring instructions.

This guide assumes your controller Pi is running at least Raspbian Jessie Lite 4.9. Running another distro or version may cause the example commands below to behave unexpectedly.

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

Clone this `Raspystat` repo into your home directory and enter the `controller` subdirectory:
```
cd ~/
git clone https://github.com/willseph/raspystat
cd raspystat/controller
```

Copy the `config.json.example` file to `config.json`:
```
cp config.json.example config.json
```

Modify the `config.json` file with your preferred editor to set up the LAN address to the Raspystat server, as well as the **secret** for the controller you are setting up (see the ***Web server*** section).

You will also need to specify the BCM-based pin numbers that correspond to the GPIO pins connected to the relay module (see ***Controller wiring*** section) for the fan/heat/cool settings.

Remove the hint lines as well.

If you are hosting the webserver on this same Pi, you may use the address `127.0.0.1` for the host. However, it is recommended that you actually use the LAN address instead, because it will act as an additional connectivity check for the watchdog to ensure the controller is still connected to the network.

Finally, run the installation script with the `-c` flag (indicating that you are installing as a controller unit):
```
cd ~/raspystat
./install.sh -c
```

The script will automatically set up all of the necessary services and jobs to ensure the sensor stays alive, or in an unrecoverable state, reboots the Pi.

If you find yourself in a scenario where the Pi is constantly rebooting, making it difficult to keep an ssh session alive, you will need to quickly modify your crontab again and remove or comment the previous addition.


### Uninstalling

In order to uninstall Raspystat from the Raspberry Pi, it is not recommended to simply delete the repo directory from the device. Uninstallation is quite easy though.

First, navigate to the root repo directory:
```
cd ~/raspystat
```

Then, call the `install.sh` script with the `-u` flag (to indicate that you wish to uninstall Raspystat from the Pi):
```
./install.sh -u
```

The script will uninstall the service unit files, as well as any Raspystat-related cron jobs from the system. After this point, you may completely delete the repo.

The log files generated by the watchdog cron job will remain, though. These logs are stored in `/var/log`, and can be removed manually after running the uninstallation script, if desired.


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
