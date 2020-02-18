Electricity Monitor Application
===============================
By John Chandler, created using numerouse sources

What is it
----------
This program is aimed at building a simple electricity usage monitor, logging data and making it available for display.

The program uses wiringPi to control the Pi GPIO for a MCP3008/MCP3208 AtoD interfacing to a non-intrusive current/voltage sensor.
The Sensor used is the SCT-013-000 100Amp/50mA current sensor.  A 22ohm burden resistor is external to the SCT sensor, 
and the input is biased to fit in the 3.3v range of the Pi. 
The divice also supports the 20 amp (SCT013-020) sensor with inbuilt burden resistor operating at 1v output.
For ease a Raspio Analog Zero board is used to mount the AtoD converter and external circuitry.

The monitored input is on A0, with A7 set to 3.3v and used to calibrate the device.

Note: the MCP3008 appears to behave as the 12 bit MCP3208 when read using the high res protocol.
