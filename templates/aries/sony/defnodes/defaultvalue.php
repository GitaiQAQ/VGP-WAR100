<?
/*rework wifi to
2.password and wpaauto psk.
*/
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/webinc/config.php";

setattr("/runtime/tmpdevdata/psk" ,"get","devdata get -e psk"); 
setattr("/runtime/tmpdevdata/wep" ,"get","devdata get -e wep"); 
setattr("/runtime/tmpdevdata/wlanmac" ,"get","devdata get -e wlan24mac");
setattr("/runtime/tmpdevdata/wlanmac_sb" ,"get","devdata get -e wlanmac");
setattr("/runtime/tmpdevdata/lanmac" ,"get","devdata get -e lanmac"); 
setattr("/runtime/tmpdevdata/ssidnum" ,"get","devdata get -e ssidnum"); 

function changes_default_wifi($phyinfuid,$ssid,$security,$password,$ssidnum)
{
	$p = XNODE_getpathbytarget("", "phyinf", "uid", $phyinfuid, 0);
	$wifi = XNODE_getpathbytarget("wifi", "entry", "uid", query($p."/wifi"), 0);
	if($p=="" || $wifi=="")
	{
		return;	
	}
	anchor($wifi); 
	
	if($ssid=="")
	{
		$ssid = query("ssid");
		$ssid = $ssid."-".$ssidnum;
	}
	//TRACE_error("ssid=".$ssid."=password=".$password."");
	if($password!="" && $ssid!="" && $security=="psk")
	{
		//chanhe the mode to wpa-auto psk
		set("authtype","WPA+2PSK");
		set("encrtype","TKIP+AES");
		set("wps/configured","1");
		set("ssid",$ssid);
		set("nwkey/psk/passphrase","1");
		set("nwkey/psk/key",$password);
		set("nwkey/wpa/groupintv","3600");
		set("nwkey/rekey/gtk","1800");
	}
	if($password!="" && $ssid!="" && $security=="wep")
	//if($password!="" && $ssid!="" && $security=="wep")
	{
		//chanhe the mode to wpa-auto psk
		set("authtype","WEPAUTO");
		set("encrtype","WEP");
		set("wps/configured","1");
		set("ssid",$ssid);
		if (strlen($password)==5 || strlen($password)==10)
			$size=64;
		else
			$size=128;

		if (strlen($password)==5 || strlen($password)==13)
			$ascii=1;
		else
			$ascii=0;
		set("nwkey/wep/size",$size);
		set("nwkey/wep/ascii",$ascii);
		set("nwkey/wep/defkey","1");
		set("nwkey/wep/key",$password);
	}
	else
	{
		TRACE_error("the mfc do not init wifi password,using default");
	}
}



$password = query("/runtime/tmpdevdata/psk");
$security = "psk";
$ssidnum = query("/runtime/tmpdevdata/ssidnum");
if ($wlanmac == "")
	$wlanmac = query("/runtime/tmpdevdata/wlanmac_sb");
changes_default_wifi($WLAN1,$ssid,$security,$password,$ssidnum);
$password = query("/runtime/tmpdevdata/wep");
$security = "wep";
$ssidnum = query("/runtime/tmpdevdata/ssidnum");
changes_default_wifi($WLAN1_GUEST,$ssid,$security,$password,$ssidnum);

/*remove links*/
del("/runtime/tmpdevdata");
?>

