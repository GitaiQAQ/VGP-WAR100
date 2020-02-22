<?
include "/htdocs/phplib/phyinf.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/etc/services/WIFI/function.php";

$UID="BAND24G";

function wifi_error($errno)
{
	fwrite("a", $_GLOBALS["START"], "event WLAN.DISCONNECTED\n");
	fwrite("a", $_GLOBALS["START"], "event STATUS.NOTREADY\n");
	fwrite("a", $_GLOBALS["START"], "exit ".$errno."\n");
	fwrite("a", $_GLOBALS["STOP"],  "event WLAN.DISCONNECTED\n");
	fwrite("a", $_GLOBALS["STOP"],  "event STATUS.NOTREADY\n");
	fwrite("a", $_GLOBALS["STOP"],  "exit ".$errno."\n");
}
function devname($uid)
{
	if ($uid==$_GLOBALS["UID"]."-1.1") return "wlan0";
	else if ($uid==$_GLOBALS["UID"]."-1.2") return "wlan0-va0";
	else if ($uid==$_GLOBALS["UID"]."-1.3") return "wlan0-va1";
	else if ($uid==$_GLOBALS["UID"]."-1.4") return "wlan0-va2";
	else if ($uid==$_GLOBALS["UID"]."-1.5") return "wlan0-va3";
	return "";
}

function general_setting($wifi_uid, $wifi_path)
{
	$stsp		= XNODE_getpathbytarget("/runtime", "phyinf", "uid", $wifi_uid, 0);
	$phyp		= XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.1", 0); //primary and second ssid use same setting
	$wifi1		= XNODE_getpathbytarget("/wifi", "entry", "uid", query($phyp."/wifi"), 0);
	$infp		= XNODE_getpathbytarget("", "inf", "uid", "BRIDGE-1", 0);
	$phyinf		= query($infp."/phyinf");
	$macaddr	= XNODE_get_var("MACADDR_".$phyinf);//xmldbc -W /runtime/services/globals
	$brinf		= query($stsp."/brinf");
	$brphyinf	= PHYINF_getphyinf($brinf);
	$winfname	= query($stsp."/name");
	$beaconinterval	= query($phyp."/media/beacon");
	$dtim		= query($phyp."/media/dtim");
	$rtsthresh	= query($phyp."/media/rtsthresh");
	$fragthresh	= query($phyp."/media/fragthresh");
	$txpower	= query($phyp."/media/txpower");
	$channel	= query($phyp."/media/channel");
	$w_partition    = query($phyp."/media/w_partition");
	$shortgi	= query($phyp."/media/dot11n/guardinterval");
	$bandwidth	= query($phyp."/media/dot11n/bandwidth");
	$rtsthresh	= query($phyp."/media/rtsthresh");
	$fragthresh	= query($phyp."/media/fragthresh");
	$ssid		= query($wifi1."/ssid");
	$opmode		= query($wifi1."/opmode");					
	$ssidhidden	= query($wifi1."/ssidhidden");
	$gk_rekey	= query($wifi1."/nwkey/wpa/groupintv");
	$wlmode		= query($phyp."/media/wlmode");
	$wmm		= query($phyp."/media/wmm/enable");
	$coexist	= query($phyp."/media/dot11n/bw2040coexist");
	$ampdu		= query($phyp."/media/ampdu");
	$protection	= query($phyp."/media/protection");
	$preamble	= query($phyp."/media/preamble");
	$fixedrate	= query($phyp."/media/txrate");
	$mcsindex	= query($phyp."/media/dot11n/mcs/index");
	$mcsauto	= query($phyp."/media/dot11n/mcs/auto");
	$phy2   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.2", 0); 
	$phy3   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.3", 0);
	$phy4   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.4", 0);
	$phy5   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.5", 0);
	$mssid1active = query($phy2."/active");
	$mssid2active = query($phy3."/active");
	$mssid3active = query($phy4."/active");
	$mssid4active = query($phy5."/active");

	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib trswitch=0\n');
	$ccode = query("/runtime/devdata/countrycode");
	if($ccode=="US")		{$REGDOMAIN="1";}
	else if ($ccode=="JP")	{$REGDOMAIN="6";}
	else if ($ccode=="TW")	{$REGDOMAIN="1";}
	else if ($ccode=="CN")	{$REGDOMAIN="3";}	
	else if ($ccode=="GB")	{$REGDOMAIN="3";}	
	else					{$REGDOMAIN="1";}
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib regdomain='.$REGDOMAIN.'\n');
	//----------------------------------------txpower setting----------------------------------------//
	if($txpower!="100"){dophp("load", "/etc/services/WIFI/txpower.php");}
	//-----------------------------------------------------------------------------------------------//
	$USE40M="";
	$SECOFFSET="";
	$SGI40M="";
	$SGI20M="";
	if($bandwidth=="20+40"){
		$USE40M="1";
		if($channel<5)		{$SECOFFSET="2";}
		else				{$SECOFFSET="1";}
		if($shortgi==400)	{$SGI40M="1";$SGI20M="1";}
		else				{$SGI40M="0";$SGI20M="0";}
	}else{
		$USE40M="0";
		if($shortgi==400)	{$SGI40M="0";$SGI20M="1";}
		else				{$SGI40M="0";$SGI20M="0";}
	}
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib use40M='.$USE40M.'\n');
	if($SECOFFSET!=""){fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib 2ndchoffset='.$SECOFFSET.'\n');}
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib shortGI40M='.$SGI40M.'\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib shortGI20M='.$SGI20M.'\n');

	if ($channel == 0){
		/*Sony wants to sopport 1~11 for worldwide. Add by Builder. */
		/*+++*/
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib ch_low=1\n');
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib ch_hi=11\n');
		/*+++*/
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib channel=0\n');
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib disable_ch14_ofdm=1\n');
	}
	else{
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib channel='.$channel.'\n');
	}

	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib MIMO_TR_mode=4\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib rtsthres=2346\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib fragthres=2346\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib bcnint='.$beaconinterval.'\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib dtimperiod='.$dtim.'\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib expired_time=30000\n');

	setssid($wifi_uid,$winfname);
	sethiddenssid($wifi_uid,$winfname);
	setwmm($wifi_uid,$winfname);

	setband($_GLOBALS["UID"]."-1.1",$winfname); //primary and second ssid use same setting
	setfixedrate($_GLOBALS["UID"]."-1.1",$winfname); //primary and second ssid use same setting

	if($opmode=="AP"){fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib opmode=16\n');}
	else{fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib opmode=16\n');}
	if($protection==0){fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib disable_protection=1\n');}
	else{fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib disable_protection=0\n');}
	if($preamble=="short"){fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib preamble=1\n');}
	else{fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib preamble=0\n');}
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib coexist='.$coexist.'\n');
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib ampdu=1\n');//aggratation.
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib amsdu=1\n');//aggratation.
	fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib stbc=0\n');
	
	if($w_partition==1){
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib block_relay=1\n');
	}
}
function wifi_service($wifi_uid, $wifi_path)
{
	$stsp		= XNODE_getpathbytarget("/runtime", "phyinf", "uid", $wifi_uid, 0);
	$phyp		= XNODE_getpathbytarget("", "phyinf", "uid", $wifi_uid, 0);
	$wifi1		= XNODE_getpathbytarget("/wifi", "entry", "uid", query($phyp."/wifi"), 0);
	$infp		= XNODE_getpathbytarget("", "inf", "uid", "BRIDGE-1", 0);
	$phyinf		= query($infp."/phyinf");
	$macaddr	= XNODE_get_var("MACADDR_".$phyinf);//xmldbc -W /runtime/services/globals
	$brinf		= query($stsp."/brinf");
	$brphyinf	= PHYINF_getphyinf($brinf);
	$winfname	= query($stsp."/name");
	$phy2   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.2", 0); 
	$phy3   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.3", 0);
	$phy4   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.4", 0);
	$phy5   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.5", 0);
	$mssid1active = query($phy2."/active");
	$mssid2active = query($phy3."/active");
	$mssid3active = query($phy4."/active");
	$mssid4active = query($phy5."/active");


	if ($wifi_uid == $_GLOBALS["UID"]."-1.1")
	{
		fwrite("a", $_GLOBALS["START"], 'ifconfig '.$winfname.' down\n');
		fwrite("a", $_GLOBALS["START"], 'flash set_mib wlan0\n');
		fwrite("a", $_GLOBALS["START"], 'brctl delif br0 '.$winfname.'\n');
//-----------------------------------MSSID setting start-------------------------------------------------------------------//

		if($mssid1active==1 || $mssid2active==1 || $mssid3active==1 || $mssid4active==1){
			fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib vap_enable=1\n');
		}
			
//-----------------------------------MSSID setting end---------------------------------------------------------------------//
		fwrite("a", $_GLOBALS["STOP"], "event WLAN.DISCONNECTED\n");
		fwrite("a", $_GLOBALS["STOP"], "event STATUS.NOTREADY\n");
	}
//----------------------------------ACL setting------------------------------------------------------------------//
		$acl_count	= query($wifi1."/acl/count");
		$acl_max	= query($wifi1."/acl/max");
		$acl_policy	= query($wifi1."/acl/policy");
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib aclnum=0\n');
		if($acl_policy=="ACCEPT")		{$ACLMODE=1;}
		else if ($acl_policy=="DROP")	{$ACLMODE=2;}
		else							{$ACLMODE=0;}
		fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib aclmode='.$ACLMODE.'\n');
		foreach ($wifi1."/acl/entry")
		{
			if ($InDeX > $acl_count || $InDeX > $acl_max) break;
			$acl_enable = query("enable");
			if ($acl_enable == 1)
			{
				$acl_list = query("mac");
				$a = cut($acl_list, "0", ":");
				$a = $a.cut($acl_list, "1", ":");
				$a = $a.cut($acl_list, "2", ":");
				$a = $a.cut($acl_list, "3", ":");
				$a = $a.cut($acl_list, "4", ":");
				$a = $a.cut($acl_list, "5", ":");
				fwrite("a", $_GLOBALS["START"], 'iwpriv '.$winfname.' set_mib acladdr='.$a.'\n');
			}
		}
//----------------------------------ACL setting END------------------------------------------------------------------//

	general_setting($wifi_uid, $wifi_path);

	$offset = cut($wifi_uid, 1, ".")-1;
	if($offset == 0)
		$mac = $macaddr;
	else
		$mac = get_mssid_mac($macaddr, $offset); 

	fwrite("a", $_GLOBALS["START"], 'ip link set '.$winfname.' addr '.$mac.'\n');
	fwrite("a", $_GLOBALS["START"], 'brctl addif br0 '.$winfname.'\n');
	fwrite("a", $_GLOBALS["STOP"], 'phpsh /etc/scripts/delpathbytarget.php BASE=/runtime NODE=phyinf TARGET=uid VALUE='.$wifi_uid.'\n');
	fwrite("a", $_GLOBALS["STOP"], 'ifconfig '.$winfname.' down\n');
	fwrite("a", $_GLOBALS["STOP"], 'brctl delif br0 '.$winfname.'\n');
}

fwrite("w",$START, "#!/bin/sh\n");
fwrite("w", $STOP, "#!/bin/sh\n");

fwrite("a",$START, "killall hostapd > /dev/null 2>&1; sleep 1\n");
fwrite("a",$STOP, "killall hostapd > /dev/null 2>&1; sleep 1\n");

$layout = query("/runtime/device/layout");

/* Get the phyinf */
$phy1	= XNODE_getpathbytarget("", "phyinf", "uid", $UID."-1.1", 0);	if ($phy1 == "")	return;
//$phyrp1	= XNODE_getpathbytarget("/runtime", "phyinf", "uid", $UID."-1.1", 0);	if ($phyrp1 == "")	return;
//$wifi1	= XNODE_getpathbytarget("/wifi", "entry", "uid", query($phy1."/wifi"), 0);
/* prepare needed config files */
$winf1	= query($phyrp1."/name");

/* Is the phyinf active? */
$active = query($phy1."/active");

$phy2   = XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.2", 0); 
$mssid1active = query($phy2."/active");

if ($layout!="bridge" && $layout!="router"){
	return;
}
else if ($active==1){
	$uid=$UID."-1.1";
	$dev=devname($uid);
	$wlan1=PHYINF_setup($uid, "wifi", $dev);
	setattr($wlan1."/txpower/ccka",		"get",	"scut -p pwrlevelCCK_A: /proc/".$dev."/mib_rf");
	setattr($wlan1."/txpower/cckb",		"get",	"scut -p pwrlevelCCK_B: /proc/".$dev."/mib_rf");
	setattr($wlan1."/txpower/ht401sa",	"get",	"scut -p pwrlevelHT40_1S_A: /proc/".$dev."/mib_rf");
	setattr($wlan1."/txpower/ht401sb",	"get",	"scut -p pwrlevelHT40_1S_B: /proc/".$dev."/mib_rf");
	fwrite("a",$START, "phpsh /etc/scripts/wifirnodes.php UID=".$uid."\n");
	wifi_service($UID."-1.1", $wifi1);

	if ($mssid1active==1){
		$uid=$UID."-1.2";
		$dev=devname($uid);
		PHYINF_setup($uid, "wifi", $dev);
		wifi_service($UID."-1.2", $wifi1);
	}
//	fwrite("a",$START, "sleep 2\n"); //Delay for driver setting complete
	fwrite("a",$START, "phpsh /etc/scripts/wpsevents.php ACTION=ADD\n"); 

	/* define WFA related info for hostapd */
	$dtype  = "urn:schemas-wifialliance-org:device:WFADevice:1";
	setattr("/runtime/hostapd/mac",  "get", "devdata get -e lanmac");
	setattr("/runtime/hostapd/guid", "get", "genuuid -s \"".$dtype."\" -m \"".query("/runtime/hostapd/mac")."\"");

	fwrite("a",$START, "xmldbc -P /etc/services/WIFI/hostapdcfg.php > /var/topology.conf\n");
	fwrite("a",$START, "hostapd /var/topology.conf &\n");
	
	fwrite("a",$START, "sleep 1\n");

	fwrite("a",$START, "event STATUS.GREEN\n");
	fwrite("a",$START, "event WLAN.CONNECTED\n");

	fwrite("a", $START, "xmldbc -P /etc/services/WIFI/updatewifistats.php -V PHY_UID=".$UID."-1.1 > /var/run/restart_upwifistats.sh\n");
	fwrite("a", $START, "sh /var/run/restart_upwifistats.sh\n");
}
else{
	wifi_error("8"); return;
}
fwrite("a",$START, "exit 0\n");
fwrite("a",$STOP,  "exit 0\n");
?>
