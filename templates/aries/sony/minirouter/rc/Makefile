# vi: set sw=4 ts=4:

-include ../../path.mk
-include ../../arch.mk
-include $(TOPDIR)/.config
-include $(TOPDIR)/Vars.mk

###########################################################################

MYNAME	:= "WRGN79/Aries/rc"
INIT0	:= S40event.sh S80telnetd.sh
INIT	:= S20init.sh

###########################################################################
all:

rootfs:
	@echo -e "\033[32mCreating rootfs for $(MYNAME) ...\033[0m"

install:
	@echo -e "\033[32mInstalling $(MYNAME) ...\033[0m"
	$(Q)for i in $(INIT); do \
	cp --remove-destination init.$$i $(TARGET)/etc/init.d/$$i; \
	chmod +x $(TARGET)/etc/init.d/$$i; \
	done
	$(Q)for i in $(INIT0); do \
	cp --remove-destination init0.$$i $(TARGET)/etc/init0.d/$$i; \
	chmod +x $(TARGET)/etc/init0.d/$$i; \
	done
	$(Q)rm -rf $(TARGET)/etc/init0.d/S41autowan.sh
	$(Q)rm -rf $(TARGET)/etc/init0.d/S65ddnsd.sh
	$(Q)rm -rf $(TARGET)/etc/init0.d/S65logd.sh

clean:

.PHONY: all rootfs install clean
