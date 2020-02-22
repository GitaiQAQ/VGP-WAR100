#!/bin/sh
echo [$0]
if [ -f /var/run/autoop-udhcpc.pid ]; then
	AUTOOPUID=`cat /var/run/autoop-udhcpc.pid`
	kill $AUTOOPUID
	xmldbc -s /runtime/device/layout router
	service WAN stop
	service LAN stop
	service BRIDGE stop
	service LAYOUT stop
	service LAYOUT start
	service BRIDGE start
	service LAN start
	service WAN start
fi
exit 0
