Electrity Monitor Application
=============================
By John Chandler, created using numerouse sources

What is it
----------
This program is aimed at building a simple electricity usage monitor, logging data and making it available for display.

The program uses wiringPi to control the Pi GPIO for a MCP3008/MCP3208 AtoD interfacing to a non-intrusive current sensor. The Sensor used is a
SCT-013-000 100Amp/50mA current sensor.  A 22ohm burden resistor is external to the SCT sensor, and the input is biased to fit in the 3.3v range of the Pi.
For ease a Raspio Analog Zero board is used to mount the AtoD converter and external circuitry.

The monitoed input is on A0, with A7 set to 3.3v and used to identify 10 or 12 bit chipset and range.

