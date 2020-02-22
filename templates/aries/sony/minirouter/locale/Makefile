# vi: set sw=4 ts=4:

-include ../../path.mk
-include ../../arch.mk
-include $(TOPDIR)/.config
-include $(TOPDIR)/Vars.mk

###########################################################################

MYNAME  := "Aries/Sony/minirouter/locale"
FILES	:= $(wildcard *.slp)

###########################################################################

all:

rootfs:
	@echo -e "\033[32mCreating rootfs for $(MYNAME) ...\033[0m"
	$(Q)[ -d $(TARGET)/etc/sealpac/langpac ] || mkdir -p $(TARGET)/etc/sealpac/langpac

install:
	@echo -e "\033[32mInstalling $(MYNAME) ...\033[0m"
	$(Q)for i in $(FILES); do \
		cp --remove-destination $$i $(TARGET)/etc/sealpac/langpac/.; \
		chmod +x $(TARGET)/etc/sealpac/langpac/$$i; \
	done

clean:
.PHONY: all rootfs install clean

