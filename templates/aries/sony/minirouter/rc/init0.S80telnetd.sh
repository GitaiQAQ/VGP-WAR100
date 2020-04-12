#!/bin/sh
echo [$0]: $1 ... > /dev/console
if [ "$1" = "start" ]; then
	if [ -f "/usr/sbin/login" ]; then
		telnetd -l /usr/sbin/login -u alpha:wrgn79 -i br0 &
	else
		telnetd &
	fi
else
	killall telnetd
fi
