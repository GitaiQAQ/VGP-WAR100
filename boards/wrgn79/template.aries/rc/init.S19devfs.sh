#!/bin/sh
insmod /lib/modules/gpio.ko
if [ ! -e /dev/gpio ]; then
	mknod /dev/gpio c 101 0
fi
