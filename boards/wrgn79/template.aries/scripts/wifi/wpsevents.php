<?
include "/htdocs/phplib/xnode.php";

echo "#!/bin/sh\n";

$uid1 = "BAND24G-1.1";

$p1 = XNODE_getpathbytarget("", "phyinf", "uid", $uid1, 0);

if ($p1=="") echo "exit 0\n";

$wifi1 = XNODE_getpathbytarget("/wifi", "entry", "uid", query($p1."/wifi"),0);

$wps = 0;
if (query($p1."/active")==1 && query($wifi1."/wps/enable")==1) $wps++;

if ($ACTION == "ADD")
{
	/* Someone uses wps, so add the events for WPS. */
	if ($wps > 0)
	{
		echo 'event DHCP.IP.CHANGE insert "'.$uid1.':service PHYINF.WIFI restart"\n';
		echo 'event WPSPIN add "/etc/scripts/wps.sh pin"\n';
		echo 'event WPSPBC.PUSH add "/etc/scripts/wps.sh pbc"\n';
	}
}
else if ($ACTION == "FLUSH")
{
	/* No body uses wps, so we can flush it. */
	if ($wps == 0)
	{
		echo "event WPSPIN flush\n";
		echo "event WPSPBC.PUSH flush\n";
	}
}

echo "exit 0\n";

?>
