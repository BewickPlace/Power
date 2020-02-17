/*
Copyright (c) 2014- by John Chandler

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
#include <pthread.h>
//#include <unistd.h>
//#include <signal.h>
//#include <assert.h>
//#include <fcntl.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <signal.h>
#include <math.h>
//#include <sys/time.h>
//#include <time.h>
//#include <sys/types.h>
//#include <sys/socket.h>
//#include <sys/uio.h>
//#include <netinet/in.h>
//#include <netinet/ip.h>
//#include <arpa/inet.h>
//#include <net/if.h>
#include <wiringPi.h>
#include <wiringPiSPI.h>
#include "mcp3208.h"

#include "power.h"
#include "errorcheck.h"
#include "application.h"

    char	my_hostname[HOSTNAME_LEN];			// Local host name
    int 	power_shutdown = 0;				// Shutdown flag

struct app 	app = {NULL, "./scripts/", NULL, 0, 0, 0, 100};	// Application key data

void usage(char *progname) {
    printf("Usage: %s [options...]\n", progname);

    printf("\n");
    printf("Mandatory arguments to long options are mandatory for short options too.\n");

    printf("\n");
    printf("Options:\n");
    printf("    -h, --help          show this help\n");

    printf("    -a, --amps          SCT013 Sensor external amp rating\n");
    printf("    -c, --config=DIR    Network Configuration file directory\n");
    printf("    -l, --log=FILE      redirect shairport's error output to FILE\n");
    printf("    -t, --track=DIR     specify Directory for tracking file (.csv)\n");

    printf("\n");
}

int parse_options(int argc, char **argv) {

    static struct option long_options[] = {
        {"help",    no_argument,        NULL, 'h'},
	{"amps",    required_argument,  NULL, 'a'},

        {"config",  required_argument,  NULL, 'c'},
        {"log",     required_argument,  NULL, 'l'},
        {"track",   required_argument,  NULL, 't'},
        {NULL, 0, NULL, 0}
    };
    int opt;

    while ((opt = getopt_long(argc, argv,
                              "+hvc:a:l:t:",
                              long_options, NULL)) > 0) {
        switch (opt) {
            default:
            case 'h':
                usage(argv[0]);
                exit(1);
            case 'a':
                app.sensor = atoi(optarg);;
                break;
            case 'v':
                debuglev++;
                break;
            case 'c':
		app.confdir = optarg;
                break;
            case 'l':
		app.logfile = optarg;
                break;
            case 't':
		app.trackdir = optarg;
                break;
        }
    }
    return optind;
}

//
//	Open Correct Logfile
//

void	open_logfile() {

    if (app.logfile) {					// Logfile is specified on command line
        int log_fd = open(app.logfile,			// Open appropriately
                O_WRONLY | O_CREAT | O_APPEND,
                S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH);
							// Warn and continue if can't open
	ERRORCHECK( log_fd < 0, "Could not open logfile", EndError);

        dup2(log_fd, STDERR_FILENO);
        setvbuf (stderr, NULL, _IOLBF, BUFSIZ);
    }
ENDERROR;
}

//
//	Signal support functions
//
static void sig_ignore(int foo, siginfo_t *bar, void *baz) {
}
static void sig_shutdown(int foo, siginfo_t *bar, void *baz) {
    power_shutdown = 1;
}

//static void sig_child(int foo, siginfo_t *bar, void *baz) {
//    pid_t pid;
//    while ((pid = waitpid((pid_t)-1, 0, WNOHANG)) > 0) {
//        if (pid == mdns_pid && !shutting_down) {
//            die("MDNS child process died unexpectedly!");
//        }
//    }
//}

static void sig_logrotate(int foo, siginfo_t *bar, void *baz) {
    open_logfile();
}


//
//	Signal Setup
//
void signal_setup(void) {
    // mask off all signals before creating threads.
    // this way we control which thread gets which signals.
    // for now, we don't care which thread gets the following.
    sigset_t set;
    sigfillset(&set);
    sigdelset(&set, SIGINT);
    sigdelset(&set, SIGTERM);
    sigdelset(&set, SIGHUP);
    sigdelset(&set, SIGSTOP);
    sigdelset(&set, SIGCHLD);
    pthread_sigmask(SIG_BLOCK, &set, NULL);

    // setting this to SIG_IGN would prevent signalling any threads.
    struct sigaction sa;
    memset(&sa, 0, sizeof(sa));
    sa.sa_flags = SA_SIGINFO;
    sa.sa_sigaction = &sig_ignore;
    sigaction(SIGUSR1, &sa, NULL);

    sa.sa_flags = SA_SIGINFO | SA_RESTART;
    sa.sa_sigaction = &sig_shutdown;
    sigaction(SIGINT, &sa, NULL);
    sigaction(SIGTERM, &sa, NULL);

    sa.sa_sigaction = &sig_logrotate;
    sigaction(SIGHUP, &sa, NULL);

    sa.sa_sigaction = &sig_ignore;
    sigaction(SIGCHLD, &sa, NULL);
}

#define SPI_CHAN 0						// SPI Channel
#define BASE	 100						// Analogue channel base
#define CURRENT_SENSOR (BASE+0)					// location of current sensor input
#define CHIPSET_SENSOR (BASE+7)					// location of input signaling chipset
#define SAMPLE_PERIOD 40					// duration of sampling at least 1 cycle in 50Hz

// System  Characteristics
#define Vext		240.0					// Mains voltage
#define	Vref		3.3					// System reference voltage
#define	ADCrange_10bit	1024					// 10 bit ADC range
#define	ADCrange_12bit	4096					// 12 bit ADC range

static int ADCrange = ADCrange_10bit;
static int ADCadj = 0;

#define DEBIAS		((ADCrange/2)-ADCadj)			// De-bias, zero point in adjusted range
// Sensor Charatcteristics
//
//	Conversion factor A0 -> kW
//	((Vref/ADCrange) * (Iext/Vint) * (Vext/1000.0))
//
#define FACTOR_100amp	((Vref/ADCrange) * (100.0/(0.05*22)) * (Vext/1000.0)) // SCT013-000 100amp, V=IR 50ma 20ohm burden resistor
#define FACTOR_20amp	((Vref/ADCrange) * (20.0 /1.1) * (Vext/1000.0))       // SCT013-020 20amp, 1v
static double factor = 0;
#define FACTOR 		factor					// Conversion factor A0 -> kW sensor specific

//
//	Determine Chipset & Sensor
//

void	determine_chipset() {
    int sample = 0;

    sample = analogRead(CHIPSET_SENSOR);			// Read from the chipset sensor

    ADCrange = (sample > (ADCrange_10bit) ? ADCrange_12bit : ADCrange_10bit); // Establish Range
    mcp3208HighResolution(ADCrange == ADCrange_12bit);		// Set driver to corect mode
    ADCadj = ADCrange - sample;
    debug(DEBUG_ESSENTIAL,"Chipset %s, range %d, sample %d adj %d\n",
				(ADCrange == ADCrange_12bit ? "MCP3208 12 bit" : "MCP3008 10 bit"), ADCrange, sample, ADCadj);
    switch(app.sensor) {
    case 100:
	FACTOR = FACTOR_100amp;
	debug(DEBUG_ESSENTIAL, "Sensor set to 100amp\n");
	break;
    case 20:
	FACTOR = FACTOR_20amp;
	debug(DEBUG_ESSENTIAL, "Sensor set to 20amp\n");
	break;
    default:
	FACTOR = FACTOR_100amp;
	debug(DEBUG_ESSENTIAL, "Invalid sensor configution defaulting to 100Amp\n");
    }
}
//
//	Read Power Consumption by sampling nd looking for peak
//
double read_powerconsumption() {
    int sample = 0;
    int Sdebias = 0;
    double Srms = 0;
    double Vrms = 0;
    double power_consumption = 0.0;
    double squares = 0;
    unsigned int sample_start;
    unsigned int sample_stop;
    int count = 0;

    sample_start = millis();					// Note start time in ms

    while (((sample_stop = millis()) - sample_start) < SAMPLE_PERIOD) {
	count++;
	sample = analogRead(CURRENT_SENSOR);			// Read from the sensor - biased V

	Sdebias = sample - DEBIAS;				// De-bias
	squares = squares + (Sdebias*Sdebias);			// First part of RMS calculation
//	debug(DEBUG_ESSENTIAL, "Sampling... %4d:%4d\n", sample, Sdebias);
	delay(1);
    }

    Srms = sqrt((double)squares/(double)count);				// 2nd part of RMS calculation
    Vrms = Srms * (Vref/ADCrange);
    power_consumption = Srms * FACTOR;

//    debug(DEBUG_TRACE,"Sampling... %d samples in  %lums, Peak:%4lu:%4lu, De-biased:%4lu, Power:%2.3f\n", count, sample_stop - sample_start, Vpeak, Vlow, Vdebias, Vdebias * FACTOR);
    debug(DEBUG_TRACE,"Sampling... %d samples in  %lums, Squares %6.0f, Srms %4.1f, Vrms:%1.3fmV, Power:%2.3fkW (%2.3f)\n", count, sample_stop - sample_start, squares, Srms, Vrms, power_consumption, FACTOR);

    return(power_consumption);
}

//
//	Main procedure
//
//
int main(int argc, char **argv) {
    int		rc = 0;
    float 	power = 0;

    signal_setup();						// Set up signal handling
    parse_options(argc, argv);					// Parse command line parameters
    open_logfile();						// Open correct logfile

    rc = gethostname(MY_NAME, HOSTNAME_LEN-1);			// Obtain local host name
    ERRORCHECK(rc < 0, "Invalid host name", EndError);
    my_hostname[HOSTNAME_LEN-1] = '\0';				// Ensure name  string terminated

    rc = wiringPiSetupPhys();					// Initialise WiringPi
    ERRORCHECK( rc < 0, "Power node failed to initialise (WiringPi)", EndError);
    rc = wiringPiSPISetup(0, 32000000);				// Setup the SPI
    ERRORCHECK( rc < 0, "Power node failed to initialise (SPI)", EndError);
    rc = mcp3208Setup(BASE,SPI_CHAN);				// and MCP3208 ADC
    ERRORCHECK( rc < 0, "Power node failed to initialise (MCP3008)", EndError);

    debug(DEBUG_ESSENTIAL, "Power node starting...\n");

    determine_chipset();					// Check 10 or 12 bit AtoD chipset
    load_power_usage();						// Load previous power data fromn log files

    while (!power_shutdown) {					// While NOT shutdown
	delay((5+2-(time(NULL)%5))*1000);			// sample every 5 seconds, aligned to 2

	power = read_powerconsumption();			// Read the sensor - kWatts
	app.power = app.power  + power;				// Maintain running total (for this logging period) kWh
	app.count++;

	debug(DEBUG_TRACE, "Value read from sensor %3.3f kW, total[%3.3f kW]\n", power, app.power);

	perform_logging();					// Perform logging if appropriate
    }

ENDERROR;
    debug(DEBUG_ESSENTIAL, "Power node shut down\n");
    return 0;
}




