/*
 * mcp3208.c:
 *	Extend wiringPi with the MCP3004 SPI Analog to Digital convertor
 *	Copyright (c) 2012-2013 Gordon Henderson
*	Extend to support MCP3208
 *	Copyright (c) 2020	John Chandler
 *
 *	Thanks also to "ShorTie" on IRC for some remote debugging help!
 ***********************************************************************
 * This file is part of wiringPi:
 *	https://projects.drogon.net/raspberry-pi/wiringpi/
 *
 *    wiringPi is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Lesser General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    wiringPi is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with wiringPi.
 *    If not, see <http://www.gnu.org/licenses/>.
 ***********************************************************************
 */

#include <wiringPi.h>
#include <wiringPiSPI.h>

#include "mcp3208.h"
#include "errorcheck.h"
/*
 * mcp3208HighResolution:
 *	Set the device to HR mode
 *********************************************************************************
 */
static int mcp3208HRmode = TRUE;

void mcp3208HighResolution(int HR) {
    mcp3208HRmode = HR;
}

/*
 * myAnalogRead:
 *	Return the analog value of the given pin
 *********************************************************************************
 */

static int myAnalogRead (struct wiringPiNodeStruct *node, int pin)
{
  unsigned char spiData [3] ;
   int chan = pin - node->pinBase ;

  if (mcp3208HRmode) {
	spiData [0] = 0b110 | chan >> 2 ;		// Start bit, single/diff, D2
	spiData [1] = chan << 6;			// D1 & D0
 	spiData [2] = 0 ;
  } else {
	spiData [0] = 1;
	spiData [1] = 0b10000000 | chan << 4;
	spiData [2] = 0 ;
  }

  wiringPiSPIDataRW (node->fd, spiData, 3) ;

  return ((spiData [1] << 8) | spiData [2]) & 0xFFF ;
}


/*
 * mcp3004Setup:
 *	Create a new wiringPi device node for an mcp3004 on the Pi's
 *	SPI interface.
 *********************************************************************************
 */

int mcp3208Setup (const int pinBase, int spiChannel)
{
  struct wiringPiNodeStruct *node ;

  if (wiringPiSPISetup (spiChannel, 1000000) < 0)
    return FALSE ;

  node = wiringPiNewNode (pinBase, 8) ;

  node->fd         = spiChannel ;
  node->analogRead = myAnalogRead ;

  mcp3208HighResolution(TRUE);
  return TRUE ;
}
