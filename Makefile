PREFIX = /usr/local

CDEBUGFLAGS = -Os -g -Wall

DEFINES = $(PLATFORM_DEFINES)

CFLAGS = $(CDEBUGFLAGS) $(DEFINES) $(EXTRA_DEFINES)

SRCS = power.c errorcheck.c application.c mcp3208.c

OBJS = power.o errorcheck.o application.o mcp3208.o

DEPS = power.h errorcheck.h application.h mcp3208.h

LDLIBS = -lrt -lwiringPi -lpthread -lm

all: power

install: power
	install -m 775 -d $(PREFIX)/bin
	install -m 775 power $(PREFIX)/bin/power
ifeq ($(wildcard /etc/power.conf),)
	install -m 666 scripts/power.conf /etc/power.conf
endif
ifeq ($(wildcard /etc/systemd/system/power.service),)
	install -m 755 scripts/power.service /etc/systemd/system/power.service
endif
ifeq ($(wildcard /var/www/html/changelog.txt),)
	install -m 644 -o www-data html/*.* /var/www/html/
endif
ifeq ($(wildcard /home/pi/monitor.py),)
	install -m 644 scripts/monitor.py /home/pi/
endif
ifeq ($(wildcard /etc/network/interfaces),)
	install -m 666 scripts/interfaces /etc/network/interfaces
endif
ifeq ($(wildcard /home/pi/monitor.py),)
	install -m 666 scripts/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
endif

clear:
	rm -f /etc/power.conf
	rm -f /etc/systemd/system/power.service
	rm -f /var/www/html/changelog.txt
	rm -f /home/pi/monitor.py
	rm -f /etc/network/interfaces
	rm -f /etc/wpa_supplicant/wpa_supplicant.conf
	rm -f /var/log/power.log

release: clear
	$(MAKE) install

%.o: %.c $(DEPS)
	$(CC) -c $(CFLAGS) $<

OBJS := $(SRCS:.c=.o)

power: $(OBJS)
	$(CC) $(CFLAGS) $(LDFLAGS) -o power $(OBJS) $(LDLIBS)


clean:
	-rm -f power
	-rm -f *.o *~ core TAGS gmon.out
