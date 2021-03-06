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
struct app {					// Application key Data
    char	*logfile;			// Debug log file
    char	*confdir;			// Directory for Heating Network conf file
    char	*trackdir;			// Directory for tracking (csv) file
    float	power;				// Summed power in a period
    int		count;				// Count ofvalue registered in period
    float	daily_power;			// Summed power in a day
    int		sensor;				// Sensor
    };

extern int power_shutdown;			// Signal heat shutdown between threads
extern struct app app;				// Application key data

#define HOSTNAME_LEN	14
#define MY_NAME		my_hostname

extern char my_hostname[];
