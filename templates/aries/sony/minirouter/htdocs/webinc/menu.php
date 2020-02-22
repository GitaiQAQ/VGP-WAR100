<? /* vi: set sw=4 ts=4: */
/* The menu definitions */


	$menu_system = I18N("h","Setting Wizard").	"|".
			I18N("h","Operation Mode").		"|".
			I18N("h","LAN").				"|".
			I18N("h","WAN").				"|".
			I18N("h","IPv6 Pass Through").	"|".
			I18N("h","UPnP").				"|".
			I18N("h","Password");
	$link_system = "wiz_set.php".		"|".
			"bsc_operation.php".		"|".
			"bsc_lan.php".	    		"|".
			"bsc_wan.php".	    		"|".
			"adv_ipbridge.php".	    	"|".
			"adv_network.php".	    	"|".
			"tools_admin.php";
			
	$menu_wireless2 = I18N("h","Basic Items").      	 "|".
            I18N("h","Multiple SSID").    	     "|".
            I18N("h","WPS").        			 "|".
			I18N("h","Access Control");
    $link_wireless2 = "bsc_wlan.php".		"|".
            "adv_mssid.php".				"|".
            "adv_wps.php".					"|".
			"firewall_macfilter.php";
			
			
	$menu_internet = I18N("h","Connection Status").       "|".
            I18N("h","Wireless Connection").			  "|".
			I18N("h","DHCP Connection");	
    $link_internet =  "st_device.php".         "|".
			"st_wlan.php".       			   "|".
            "st_dhcpc.php";

	$menu_tools = I18N("h","Initialization").		"|".
			I18N("h","Firmware").					"|".
			I18N("h","Software License");
	$link_tools = "tools_system.php".			"|".
			"tools_firmware.php".				"|".
			"st_license.php";


?>
