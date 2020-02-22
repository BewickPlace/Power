/*
Copyright (c) 2014-  by John Chandler

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.  IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <errno.h>
#include <getopt.h>

//#include <unistd.h>
//#include <signal.h>
//#include <assert.h>
//#include <fcntl.h>
#include <sys/time.h>
#include <time.h>
//#include <sys/types.h>
//#include <sys/socket.h>
//#include <sys/uio.h>
//#include <netinet/in.h>
//#include <netinet/ip.h>
//#include <arpa/inet.h>
//#include <net/if.h>

#include "errorcheck.h"
#include "power.h"
#include "application.h"

void	midnight_processing(struct tm *info);
void	perform_daily_log(struct tm *info);

//
//	Perform Power Logging
//
void	perform_logging() {
    char 	logfile[50];			// Log file
    time_t	seconds;
    struct tm	*info;
    FILE	*log;
    int	exists = 0;

    seconds = time(NULL);				// get the time
    info = localtime(&seconds);				// convert into strctured time

    if (info->tm_min % 5 > 0) { goto EndError; }	// Perform Logging on 5 minute interval
    if (info->tm_sec > 5) { goto EndError; }		// in first 5 seconds
    debug(DEBUG_TRACE, "Performing logging at %d:%d:%d\n", info->tm_hour, info->tm_min, info->tm_sec);

    if (app.trackdir == NULL) { goto EndError; }	// Do NOT log is directory not specified
    sprintf(logfile,"%s%s_%04d-%02d-%02d.csv", app.trackdir, MY_NAME, info->tm_year + 1900, info->tm_mon + 1, info->tm_mday);
    ERRORCHECK( strlen(logfile) > sizeof(logfile), "Tracking filename too long", TrackError);

    log = fopen(logfile, "r");				// Open  the file
    if (log != NULL) {					// if file exists
	exists = 1;
	fclose(log);
    }
    log = fopen(logfile, "a"); 				// open Tracking file for Append
    ERRORCHECK( (log == NULL) , "Error opening Tracking file", OpenError);

    if (exists == 0) { fprintf(log, "Time, Power Usage\n"); }

    app.power = app.power/app.count;			// Take average consumption
    app.daily_power = app.daily_power + (app.power/12);	// Maintain daily running total in kWh (12 5 mins intervals per hour)
    fprintf(log, "%02d:%02d,%f\n", info->tm_hour, info->tm_min, app.power); // Perform Logging
    debug(DEBUG_ESSENTIAL, "Interval Power %3.3f kW, Daily Total %3.3f kWh\n", app.power, app.daily_power);
    app.power = 0;					// & reset count over logging period
    app.count = 0;
    fclose(log);

    midnight_processing(info);				// Perform End/Start of Day Processing if appropriate

ERRORBLOCK(TrackError);
    debug(DEBUG_ESSENTIAL, "Size: %d:%d %s\n", strlen(logfile), sizeof(logfile), logfile);

ERRORBLOCK(OpenError);
    debug(DEBUG_ESSENTIAL, "Logfile %s Append errno: %d\n2", logfile, errno);
ENDERROR;
}

//
//	Midnight processing
//
void	midnight_processing(struct tm *info) {
    char 	logfile[50];			// Log file
    int		day, month, year;
    int		i;

    if ((info->tm_hour == 23) && (info->tm_min >= 55)){	// if Last log interval before Midnight
	debug(DEBUG_TRACE, "Pre-Midnight precessing....\n");

	perform_daily_log(info);			// Record key daily summary
    }

    if (!((info->tm_hour == 0) && (info->tm_min == 0))) return;	// Exit if not Midnight
    debug(DEBUG_TRACE, "Midnight precessing....\n");

    // Delete old Track files - beyond 30 days, 7 day window

    for (i = 0 ; i < 7; i++) {
	day = ((info->tm_mday - 1 + 31 - i) % 31) + 1;
	month = ((info->tm_mon - 1 + 12 - (day > info->tm_mday)) % 12) + 1;
	year = (info->tm_year  - (month > info->tm_mon));
	sprintf(logfile,"%s%s_%04d-%02d-%02d.csv", app.trackdir, MY_NAME, year + 1900, month, day);
	ERRORCHECK( strlen(logfile) > sizeof(logfile), "Tracking filename too long", EndError);

	debug(DEBUG_TRACE, "Delete logfile %s (%d/%d)\n", logfile, remove(logfile), errno);
    }

ENDERROR;
}

//
//	Perform Daily Logging
//
void	perform_daily_log(struct tm *info) {
    char 	logfile[50];			// Log file
    FILE	*log;
    int	exists = 0;

    if (app.trackdir == NULL) { goto EndError; }	// Do NOT log if directory not specified

    sprintf(logfile,"%s%s_%04d.csv", app.trackdir, MY_NAME, info->tm_year + 1900);
    ERRORCHECK( strlen(logfile) > sizeof(logfile), "Tracking filename too long", TrackError);

    log = fopen(logfile, "r");				// Open  the file
    if (log != NULL) {					// if file exists
	exists = 1;
	fclose(log);
    }
    log = fopen(logfile, "a"); 				// open Tracking file for Append
    ERRORCHECK( (log == NULL) , "Error opening Tracking file", OpenError);

    if (exists == 0) { fprintf(log, "Date, Power Usage\n"); }
    fprintf(log, "%02d-%02d,%f\n", info->tm_mday, (info->tm_mon+1), app.daily_power);
    app.daily_power = 0;				// Reset daily running total

    fclose(log);

ERRORBLOCK(TrackError);
    debug(DEBUG_ESSENTIAL, "Size: %d:%d %s\n", strlen(logfile), sizeof(logfile), logfile);

ERRORBLOCK(OpenError);
    debug(DEBUG_ESSENTIAL, "Logfile %s Append errno: %d\n2", logfile, errno);
ENDERROR;
}

//
//	Load Power Usage
//
void	load_power_usage() {
    char 	logfile[50];			// Log file
    time_t	seconds;
    struct tm	*info;
    FILE	*log;
    float	power;

    if (app.trackdir == NULL) { goto EndError; }	// Do NOT log if directory not specified

    seconds = time(NULL);				// get the time
    info = localtime(&seconds);				// convert into strctured time

    sprintf(logfile,"%s%s_%04d-%02d-%02d.csv", app.trackdir, MY_NAME, info->tm_year + 1900, info->tm_mon + 1, info->tm_mday);
    ERRORCHECK( strlen(logfile) > sizeof(logfile), "Tracking filename too long", TrackError);

    log = fopen(logfile, "r");				// Open  the file
    if (log != NULL) {					// if file exists

	app.power = 0;					// Reset power running totals
	app.daily_power = 0;

	fscanf(log, "%*[^\n]\n");			// Skip header
	while (fscanf(log, "%02d:%02d,%f\n", &info->tm_hour, &info->tm_min, &power) >0) {
	    app.daily_power = app.daily_power + (power/12); // convert to Daily usage in Kwh (12 5 mins values per hour
	}
	debug(DEBUG_ESSENTIAL, "Power usage so far today updated, %3.3f kWh\n", app.daily_power);
	fclose(log);
    }

ERRORBLOCK(TrackError);
    debug(DEBUG_ESSENTIAL, "Size: %d:%d %s\n", strlen(logfile), sizeof(logfile), logfile);
ENDERROR;
}
