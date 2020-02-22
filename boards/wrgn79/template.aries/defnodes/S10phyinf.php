<?
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/xnode.php";
/*UID will depend on the prefix in default.xml.*/
$UID	= "BAND24G";
$wlan1	= PHYINF_setup($UID."-1.1", "wifi", "wlan0");
set($wlan1."/media/band", "11GN");
setattr($wlan1."/txpower/ccka",		"get",	"scut -p pwrlevelCCK_A: /proc/wlan0/mib_rf");
setattr($wlan1."/txpower/cckb",		"get",	"scut -p pwrlevelCCK_B: /proc/wlan0/mib_rf");
setattr($wlan1."/txpower/ht401sa",	"get",	"scut -p pwrlevelHT40_1S_A: /proc/wlan0/mib_rf");
setattr($wlan1."/txpower/ht401sb",	"get",	"scut -p pwrlevelHT40_1S_B: /proc/wlan0/mib_rf");
?>
