# vi: set sw=4 ts=4:
#
# Makefile for services of Aries.
#

-include ../../path.mk
-include ../../arch.mk
-include $(TOPDIR)/.config
-include $(TOPDIR)/Vars.mk

###########################################################################

MYNAME	:= "Aries/services"
MYMAKE	:= make V=$(V) DEBUG=$(DEBUG)
SERVICES:= LAN WAN BRIDGE NAMERESOLV  NAMERESOLV.BRIDGE-1 MULTICAST DEVICE.PASSTHROUGH
DONT_NEED_SERVICE := SAMBA SMS SMS.SEND
DIRS	:= INET HTTP NAMERESOLV 

###########################################################################

all:

rootfs:
	@echo -e "\033[32mCreating rootfs for $(MYNAME) ...\033[0m"
	$(Q)[ -d $(TARGET)/etc/services ] || mkdir -p $(TARGET)/etc/services
	$(Q)[ -d $(TARGET)/etc/config ] || mkdir -p $(TARGET)/etc/config

install:
	@echo -e "\033[32mInstalling $(MYNAME) ...\033[0m"
	$(Q)for i in $(SERVICES); do cp $$i.php $(TARGET)/etc/services/.; done
	$(Q)for i in $(DIRS); do \
	if [ -f $$i/Makefile ]; then \
		$(MYMAKE) -C $$i install; \
	else \
		[ -d $(TARGET)/etc/services/$$i ] || mkdir -p $(TARGET)/etc/services/$$i ; \
		cp $$i/*.php $(TARGET)/etc/services/$$i/.; \
	fi \
	done
	$(Q)for i in $(DONT_NEED_SERVICE); do \
		rm -f $(TARGET)/etc/services/$$i.php; \
	done
	$(Q)rm -rf $(TARGET)/etc/services/BWC
	$(Q)rm -rf $(TARGET)/etc/services/DDNS
	$(Q)rm -rf $(TARGET)/etc/services/DDNS4.INF.php
	$(Q)rm -rf $(TARGET)/etc/services/DDNS4.WAN-1.php
	$(Q)rm -rf $(TARGET)/etc/services/DDNS4.WAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/DDNS4.WAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/DEVICE.TIME.php
	$(Q)rm -rf $(TARGET)/etc/services/DHCPS4.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/DHCPS4.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/DHCPS4.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/DHCPS4.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/DHCPS4.LAN-6.php
	$(Q)rm -rf $(TARGET)/etc/services/DMZ.NAT-1.php
	$(Q)rm -rf $(TARGET)/etc/services/DNS4.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/DNS4.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/DNS4.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/DNS4.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/DNS4.LAN-6.php
	$(Q)rm -rf $(TARGET)/etc/services/HTTP.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/HTTP.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/HTTP.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/HTTP.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/HTTP.LAN-6.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.LAN-6.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.WAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.WAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.WAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/INET.WAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.LAN-6.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.WAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.WAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/INFSVCS.WAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/CALLMGR.php
	
	
	$(Q)rm -rf $(TARGET)/etc/services/NAT.php
	$(Q)rm -rf $(TARGET)/etc/services/FIREWALL6.php
	$(Q)rm -rf $(TARGET)/etc/services/FIREWALL-2.php
	$(Q)rm -rf $(TARGET)/etc/services/FIREWALL-3.php
	$(Q)rm -rf $(TARGET)/etc/services/FIREWALL.php
	$(Q)rm -rf $(TARGET)/etc/services/FIREWALL
	$(Q)rm -rf $(TARGET)/etc/services/ITUNES.php
	$(Q)rm -rf $(TARGET)/etc/services/URLCTRL.php
	$(Q)rm -rf $(TARGET)/etc/services/VSVR.NAT-1.php
	$(Q)rm -rf $(TARGET)/etc/services/PFWD.NAT-1.php
	$(Q)rm -rf $(TARGET)/etc/services/ACCESSCTRL.php
	$(Q)rm -rf $(TARGET)/etc/services/PORTT.NAT-1.php
	$(Q)rm -rf $(TARGET)/etc/services/ROUTE.STATIC.php
	$(Q)rm -rf $(TARGET)/etc/services/ROUTE.IPUNNUMBERED.php
	$(Q)rm -rf $(TARGET)/etc/services/ROUTE.DESTNET.php
	
	
	$(Q)rm -rf $(TARGET)/etc/services/QOS.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTDMZ.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTFIREWALL.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTPFWD.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTPORTT.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTURLCTRL.php
	$(Q)rm -rf $(TARGET)/etc/services/IPTVSVR.php
	$(Q)rm -rf $(TARGET)/etc/services/UPNP.LAN-2.php
	$(Q)rm -rf $(TARGET)/etc/services/UPNP.LAN-3.php
	$(Q)rm -rf $(TARGET)/etc/services/UPNP.LAN-4.php
	$(Q)rm -rf $(TARGET)/etc/services/UPNP.LAN-5.php
	$(Q)rm -rf $(TARGET)/etc/services/UPNP.LAN-6.php
	
	
clean:

.PHONY: all rootfs install clean
