#!/bin/sh
echo [$0]: $1 ... > /dev/console
if [ "$1" = "start" ]; then
event WAN-1.UP		add "service INFSVCS.WAN-1 restart"
event WAN-1.DOWN	add "service INFSVCS.WAN-1 stop"
event LAN-1.UP		add "service INFSVCS.LAN-1 restart"
event LAN-1.DOWN	add "service INFSVCS.LAN-1 stop"
event BRIDGE-1.UP	add "service INFSVCS.BRIDGE-1 restart"
event BRIDGE-1.DOWN	add "service INFSVCS.BRIDGE-1 stop"
event BRIDGE-2.UP	add "service INFSVCS.BRIDGE-2 restart"
event BRIDGE-2.DOWN	add "service INFSVCS.BRIDGE-2 stop"


event REBOOT		add "/etc/events/reboot.sh"
event FRESET		add "/etc/events/freset.sh"
event DBSAVE		add "/etc/scripts/dbsave.sh"
event UPDATERESOLV	add "/etc/events/UPDATERESOLV.sh"
event SEALPAC.SAVE	add "/etc/events/SEALPAC-SAVE.sh"
event SEALPAC.LOAD	add "/etc/events/SEALPAC-LOAD.sh"
event SEALPAC.CLEAR	add "/etc/events/SEALPAC-CLEAR.sh"
event DNSCACHE.FLUSH	add "/etc/events/DNSCACHE-FLUSH.sh"
event DHCPS4.RESTART	add "/etc/events/DHCPS-RESTART.sh"
event INF.RESTART	add "phpsh /etc/events/INF-RESTART.php"
event WAN.RESTART	add "phpsh /etc/events/INF-RESTART.php PREFIX=WAN"
event LAN.RESTART	add "phpsh /etc/events/INF-RESTART.php PREFIX=LAN"
event BRIDGE.RESTART	add "phpsh /etc/events/INF-RESTART.php PREFIX=BRIDGE"

event SEALPAC.LOAD
service DNS alias DNS4.INF
fi
