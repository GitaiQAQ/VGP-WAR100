#!/bin/sh
echo [$0]: $1 $interface $ip $subnet $router $lease $domain $scope $winstype $wins $sixrd_prefix $sixrd_prefixlen $sixrd_msklen $sixrd_bripaddr ... > /dev/console
if [ "$1" != "bound" ]; then
exit 0
fi
IPA=`echo $ip | cut -d. -f1`
IPB=`echo $ip | cut -d. -f2`
if [ "$IPA" = "192" ] && [ "$IPB" = "168" ]; then
	MODE="bridge"
elif [ "$IPA" = "10" ]; then
	MODE="bridge"
elif [ "$IPA" = "172" ] && [ "$IPB" -ge "16" ] && [ "$IPB" -le "31" ]; then
	MODE="bridge"
else
	MODE="router"
fi
	xmldbc -s /runtime/device/layout $MODE
	AUTOOPUID=`cat /var/run/autoop-udhcpc.pid`
	kill $AUTOOPUID
	xmldbc -k endauto
	service WAN stop
	service LAN stop
	service BRIDGE stop
	service LAYOUT stop
	service LAYOUT start
	service BRIDGE start
	service LAN start
	service WAN start
exit 0
