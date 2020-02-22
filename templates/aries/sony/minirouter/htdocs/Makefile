# vi: set sw=4 ts=4:

include ../../path.mk
include ../../arch.mk
-include $(TOPDIR)/.config
-include $(TOPDIR)/Vars.mk

###########################################################################

MYNAME	:= "Aries/Sony/minirouter/htdocs"
HTDOCS	:= webinc web phplib
SUBDIRS	:=
DONT_NEED_FILES := adv_dlna.php adv_gzone.php adv_itunes.php adv_wlan_wifitest.php bsc_media_server.php bsc_sms_inbox.php bsc_sms_inbox_rlt.php bsc_sms.php bsc_sms_send.php bsc_wlan_wifitest.php dlna_refresh.php dlnastate.php shareport.php st_missed_call.php time.php obfilter.php firewall.php pfwd.php bwc.php

###########################################################################

all:

rootfs:
	@echo -e "\033[32mCreating rootfs for $(MYNAME) ...\033[0m"
	$(Q)[ -d $(TARGET)/htdocs ] || mkdir -p $(TARGET)/htdocs

install:
	@echo -e "\033[32mInstalling $(MYNAME) ...\033[0m"
	$(Q)for i in $(HTDOCS); do cp -r --remove-destination $$i $(TARGET)/htdocs/. ; done
	$(Q)for i in $(SUBDIRS); do cp -r --remove-destination $$i $(TARGET)/htdocs/. ; done
	$(Q)find $(TARGET)/htdocs -name .svn -type d | xargs rm -rf
	$(Q)for i in $(DONT_NEED_FILES); do find $(TARGET)/htdocs -name $$i -type f | xargs rm -f; done

	
	#==20121130 jack remove from prog/htdocs/webinc/phplib
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DDNS4.INF.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DDNS4.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DDNS4.WAN-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/FIREWALL-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/FIREWALL-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/FIREWALL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/NAT.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/RUNTIME.DDNS4.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/SCHEDULE.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DHCPS4.LAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DMZ.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/QOS.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.LAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.LAN-3.php	
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.LAN-4.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.LAN-5.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.LAN-6.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.WAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.WAN-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.WAN-4.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INET.WAN-5.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/SMS.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/SMS.SEND.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/SIM.LOCK.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/SIM.CHK.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/URLCTRL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DEVICE.TIME.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/VSVR.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/PFWD.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/PORTT.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/CALLMGR.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/ROUTE.STATIC.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/ROUTE.IPUNNUMBERED.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/ROUTE.DESTNET.php
	

	#==20121130 jack remove from sony/htdocs/webinc/phplib
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/ACCESSCTRL.php
	
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DEVICE.LOG.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/BWC.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/DDNS6.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/EMAIL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/INBFILTER.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/ITUNES.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/OBFILTER-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/OBFILTER.php

	#==20121130 jack remove filefolder
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/OBFILTER
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/PFWD
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/FIREWALL
	$(Q)rm -rf $(TARGET)/htdocs/phplib/fatlady/BWC
	
	
	

	#==20121130 jack remove setcfg connect to fatlady
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DDNS4.INF.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DDNS4.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DDNS4.WAN-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/FIREWALL-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/FIREWALL-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/FIREWALL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/NAT.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/RUNTIME.DDNS4.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/SCHEDULE.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DHCPS4.LAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DMZ.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/QOS.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.LAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.LAN-3.php	
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.LAN-4.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.LAN-5.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.LAN-6.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.WAN-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.WAN-3.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.WAN-4.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INET.WAN-5.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/SMS.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/SMS.SEND.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/SIM.LOCK.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/SIM.CHK.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/URLCTRL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DEVICE.TIME.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/VSVR.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/PFWD.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/PORTT.NAT-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/BWC.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DEVICE.LOG.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/CALLMGR.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/ROUTE.STATIC.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/ROUTE.IPUNNUMBERED.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/ROUTE.DESTNET.php
	
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/ACCESSCTRL.php
    
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/DDNS6.WAN-1.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/EMAIL.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/INBFILTER.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/ITUNES.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/OBFILTER-2.php
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/OBFILTER.php
    
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/OBFILTER
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/PFWD
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/FIREWALL
	$(Q)rm -rf $(TARGET)/htdocs/phplib/setcfg/BWC
	
	
	
clean:

.PHONY: all rootfs install clean
