#!/bin/sh
echo [$0]: $1 ... > /dev/console
if [ "$1" = "start" ]; then
    event "STATUS.READY"        add "usockc /var/gpio_ctrl STATUS_GREEN"
    event "STATUS.CRITICAL"     add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK"
    event "STATUS.NOTREADY"     add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK"
    event "STATUS.GREEN"        add "usockc /var/gpio_ctrl STATUS_GREEN"
    event "STATUS.GREENBLINK"   add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK"
	event "INET.CONNECTED"		add "usockc /var/gpio_ctrl INET_GREEN"
	event "INET.DISCONNECTED"	add "usockc /var/gpio_ctrl INET_GREEN_BLINK"
	event "WAN-1.CONNECTED"		add "usockc /var/gpio_ctrl INET_GREEN"
	event "WAN-1.PPP.ONDEMAND"  add "usockc /var/gpio_ctrl INET_GREEN_BLINK"
	event "WAN-1.DISCONNECTED"  add "usockc /var/gpio_ctrl INET_GREEN_BLINK"

	event "WPS.INPROGRESS"		add "usockc /var/gpio_ctrl WPS_IN_PROGRESS"
	event "WPS.SUCCESS"			add "usockc /var/gpio_ctrl WPS_SUCCESS"
	event "WPS.OVERLAP"			add "usockc /var/gpio_ctrl WPS_OVERLAP"
	event "WPS.ERROR"			add "usockc /var/gpio_ctrl WPS_ERROR"
	event "WPS.NONE"			add "usockc /var/gpio_ctrl WPS_NONE"
	event "WPSPBC.LEDBLINK"		add	"phpsh /etc/events/WPSPBC_LED_BLINK.php"
	event "WLAN.CONNECTED"      add "usockc /var/gpio_ctrl WLAN_ENABLED"
	event "WLAN.DISCONNECTED"   add "usockc /var/gpio_ctrl WLAN_DISABLED"
fi
