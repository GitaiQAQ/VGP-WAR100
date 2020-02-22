<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";

function startcmd($cmd)	{fwrite(a,$_GLOBALS["START"],$cmd."\n");}
function stopcmd($cmd)	{fwrite(a,$_GLOBALS["STOP"], $cmd."\n");}
function error($err)	{startcmd("exit ".$err); stopcmd("exit ".$err); return $err;}

/****************************************************************/
function infsvcs_setup($name, $cfg, $sts, $ipv6)
{
	anchor($cfg);

	$web	= query("web");
	//$hnap	= query("hnap");
	$upnp	= query("upnp/count");
	$dhcps4	= query("dhcps4");
	$dhcpc6	= query("dhcpc6");
	$dhcps6	= query("dhcps6");
	$ddns4	= query("ddns4");
	$ddns6	= query("ddns6");
	$dns4	= query("dns4");
	$dns6	= query("dns6");
	$dns    = query("dns");
	$defrt	= query("defaultroute");
	$bwc 	= query("bwc");
	$sshd   = query("sshd"); //sandy add
	$nameresolve   = query("nameresolve"); //sam_pan add
	$next_cnt = query("infnext#");
    //$next   = query("infnext");
	$child  = query("child");
	$childgz  = query("childgz");
	$stunnel= query("stunnel");

	//if (""!=$web)		startcmd("service HTTP."		.$name." start");
	if (""!=$web && $ipv6>0)
	{	
			startcmd("sleep 1");	
			startcmd("service HTTP."		.$name." start");
	}
	else	startcmd("service HTTP."		.$name." start");

	//if ($hnap==1)		startcmd("service HNAP."		.$name." start");
	if ($upnp>0)		startcmd("service UPNP."		.$name." start");
	if ($nameresolve>0)	startcmd("service NAMERESOLV."	.$name." start");

	/* stunnel service */
	if ($stunnel==1)	startcmd("service STUNNEL start");

	if ($ipv6>0)
	{
		if (""!=$dhcps6)	startcmd("service DHCPS6."	.$name." restart");//rbj
		if (""!=$dns6)		startcmd("service DNS6."	.$name." start");
	}
	else
	{
		if (""!=$dhcps4)	startcmd("service DHCPS4."	.$name." start");
		if (""!=$dns4)		startcmd("service DNS4."	.$name." start");
	}

	if (""!=$dns)
	{
		startcmd("service DNS.INF alias DNS");
		startcmd("service DNS start"); /* Unified DNS services. */
	}

	if (""!=$dhcpc6 && "0"!=$dhcpc6)	startcmd("service DHCPC6."		.$name." start");

	if (""!=$bwc) startcmd("service BWC.".$name." restart");

	startcmd("event ".$name.".CONNECTED");
	//if (""!=$next)	startcmd("service INET.".$next." start");
	$i=1;
	while($i <= $next_cnt)
	{
		$next = query("infnext:".$i);
		if (""!=$next)
		{
			//startcmd("sleep 20");//wait for dslite info */	
			startcmd("service INET.".$next." start");
			stopcmd("service INET."	.$next." stop");
		}
		$i++;
	}
	if (""!=$child)
	{
		//startcmd("service INET.CHILD.".$child." alias INF.CHILD.".$child);
		//startcmd("service INET.CHILD.".$child." restart");
		startcmd("service INET.".$child." restart");
	}

	if (""!=$childgz)
	{
		startcmd("service INET.".$childgz." restart");
	}
	stopcmd("event ".$name.".DISCONNECTED");

	/* Stop services .................................................. */
	/*if (""!=$child)		stopcmd("service INET.CHILD."	.$child." stop");*/
	/*if (""!=$child)		stopcmd("service INET."			.$child." stop");*/
	if (""!=$child)		stopcmd("phpsh /etc/scripts/stopchild.php CHILDUID=".$child);
	if (""!=$childgz)		stopcmd("phpsh /etc/scripts/stopchild.php CHILDUID=".$childgz);
	//if (""!=$next)		stopcmd("service INET."			.$next." stop");
	if (""!=$bwc)		stopcmd("service BWC."			.$name." stop");
	if (""!=$dhcpc6)	stopcmd("service DHCPC6."		.$name." stop");
	if (""!=$dns)       stopcmd("service DNS stop");

	/* These services may be started after the interface was up.
	 * Stop them even if they were not started at the interface up. */
	if ($ipv6>0)
	{
		stopcmd("service DNS6."		.$name." stop");
		stopcmd("service DHCPS6."	.$name." stop");
	}
	else
	{
		stopcmd("service DNS4."		.$name." stop");
		stopcmd("service DHCPS4."	.$name." stop");
	}

	stopcmd("service UPNP."		.$name." stop");
	stopcmd("service HTTP."		.$name." stop");

	//if ($hnap==1)		stopcmd("service HNAP."		.$name." stop");
	if ($nameresolve>0)	stopcmd("service NAMERESOLV.".$name." stop");
}


//==20121217 jack add for prevent lan ip conflict with wan ip==//
//==add one global node at /runtime/services/globals/var/ipconflict==//
//==and use inet_ipv4.php to check ip address==//
function infsvcs_pre_ipconflict()
{
	if(query("/runtime/device/layout") == "router")
	{
		$uid = "WAN-1";
		$cfg = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
		$sts_wan = XNODE_getpathbytarget("/runtime", "inf", "uid", $uid, 0);
		if ($cfg=="" || $sts_wan=="")
		{
			SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$uid.") not exist.");
			SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$uid.") not exist.");
			return error(9);
		}
		$wan_ipaddr = query($sts_wan."/inet/ipv4/ipaddr");
		if($wan_ipaddr == "") return;
		TRACE_debug("wan_ipaddr=".$wan_ipaddr);
		
		$uid = "LAN-1";
		$cfg = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
		$sts_lan = XNODE_getpathbytarget("/runtime", "inf", "uid", $uid, 0);
		if ($cfg=="" || $sts_lan=="")
		{
			SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$uid.") not exist.");
			SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$uid.") not exist.");
			return error(9);
		}
		$lan_ipaddr = query($sts_lan."/inet/ipv4/ipaddr");
		if($lan_ipaddr == "") return;
		TRACE_debug("lan_ipaddr=".$lan_ipaddr);
		
		$lan_a = cut($lan_ipaddr, 0, ".");
		$lan_b = cut($lan_ipaddr, 1, ".");
		$lan_c = cut($lan_ipaddr, 2, ".");
		$lan_d = cut($lan_ipaddr, 3, ".");
		
		$wan_a = cut($wan_ipaddr, 0, ".");
		$wan_b = cut($wan_ipaddr, 1, ".");
		$wan_c = cut($wan_ipaddr, 2, ".");
		$wan_d = cut($lan_ipaddr, 3, ".");
		
		
		if($lan_a == $wan_a)
		{
			if($lan_b == $wan_b)
			{
				if($lan_c == $wan_c)
				{
					startcmd("service INET.LAN-1 stop");
					XNODE_set_var("ipconflict","yes");
					startcmd("service INET.LAN-1 start");
					//XNODE_del_var($name)
					//XNODE_getpathbytarget("/runtime/services/globals", "var", "name", "ipconflict", 1);
					//set($sts_lan."runtime/services/globals/var",$lan_a.".".$lan_b.".".$lan_c.".".$lan_d);
				}
			}
		}
	}
}
//==20121217 jack add for prevent lan ip conflict with wan ip==//


function infsvcs_wan($index)
{
	$uid = "WAN-".$index;
	$cfg = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
	$sts = XNODE_getpathbytarget("/runtime", "inf", "uid", $uid, 0);
	if ($cfg=="" || $sts=="")
	{
		SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$uid.") not exist.");
		SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$uid.") not exist.");
		return error(9);
	}
	$upper		= query($cfg."/upperlayer");
	$lower		= query($cfg."/lowerlayer");
	$addrtype	= query($sts."/inet/addrtype");
	if ($addrtype=="ipv6" || $addrtype=="ppp6") $ipv6=1; else $ipv6=0;

	/* Firewall */
	$fw	= query("/acl/firewall/count")+query("/acl/firewall2/count")+query("/acl/firewall3/count");
	/* IPv6 Firewall */
	$fw6= query("/acl6/firewall/count")+0;

	/* Tell everybody, we are going down.
	 * Trigger this event before anything. */
	stopcmd("event INFSVCS.".$uid.".DOWN");


	/*************************************************************************/
	/* TODO: The following code is a wrong example and need to be corrected.
	 * Never ever assume an interface will have a certain type. WAN-3 is not
	 * always a 3G interface.	David Hsieh <david_hsieh@alphanetworks.com> */

	/* Some 3G adapters cannot disconnect ,they need to cmd some AT command. */
	if ($index == "3") stopcmd("event DIALINIT");
	/*************************************************************************/

	stopcmd("event UPNP.IGD.NOTIFY.WANIPCONN1");
	stopcmd("event UPDATERESOLV");

	/*************************************************************************/
	/* TODO: The following code is problematic, it will only be run at UP, not DOWN.
	 * David Hsieh <david_hsieh@alphanetworks.com> */

	/* To make sure "Automatic Uplink Speed" of QOS can re-detect when wan up/down. (sam_pan)*/
	set("/runtime/device/qos/bwup","0");
	set("/runtime/device/qos/monitor","0");
	/*************************************************************************/

	/* If we have no lowerlayer, we need to restart these services. */
	if ($lower=="")
	{
		if ($ipv6>0)
		{
			if ($fw6>0) stopcmd("service FIREWALL6 restart");
			stopcmd("service FIREWALL6 restart");
			stopcmd("service IP6TDEFCHAIN restart");
			stopcmd("service IP6TSMPSECURITY restart");		
		}
		else
		{
			stopcmd("service MULTICAST restart");
			stopcmd("service IPTDEFCHAIN restart");
			if ($fw>0) stopcmd("service FIREWALL restart");
		}
	}


	/* Walk through the WAN services */
	infsvcs_setup($uid, $cfg, $sts, $ipv6);

	/* If we have no upperlayer, we need to restart these services. */
	if ($upper=="")
	{
		if ($ipv6>0)
		{
			if ($fw6>0) startcmd("service FIREWALL6 restart");
			startcmd("service IP6TDEFCHAIN restart");
			startcmd("service FIREWALL6 restart");
			startcmd("service IP6TSMPSECURITY restart");
			startcmd("service MULTICAST restart");
		}
		else
		{
			if ($fw>0) startcmd("service FIREWALL restart");
			startcmd("service IPTDEFCHAIN restart");
			startcmd("service MULTICAST restart");
		}
	}

	startcmd("event UPDATERESOLV");

	if ($ipv6==0)
	{
		/*Check LAN DHCP setting. We will resatrt DHCP server if the DNS relay is disabled*/
		foreach ("/inf")
		{
			$disable= query("disable");
			$active = query("active");
			$dhcps4 = query("dhcps4");
			$dns4   = query("dns4");
			$dns    = query("dns");
			if ($disable != "1" && $active=="1" && $dhcps4!="")
			{
				if ($dns4 == "" && $dns == "") startcmd("event DHCPS4.RESTART");
			}
		}
	}

	startcmd("event INFSVCS.".$uid.".UP");
	startcmd("event UPNP.IGD.NOTIFY.WANIPCONN1");
	startcmd("service UPNPC restart\n");

//marco, if wireless is auto channel, we use updatewifistats to update channel to runtime node
//of our DB. Since we save this info to runtime node, and runtime node index will be changed 
//due to interface up down. So the channel will write to wrong index. And if we see the channel 
//in webUI the information will be wrong.
	$i=1;
	while ($i>0)
	{
		$uid = "BAND24G-1.".$i;
		$p = XNODE_getpathbytarget("", "phyinf", "uid", $uid, 0);
		if ($p=="") {$i=0; break;}
		$active=query($p."/active");
		if($active=="1")
		{
			startcmd("xmldbc -P /etc/services/WIFI/updatewifistats.php -V PHY_UID=".$uid." > /var/run/restart_upwifistats.sh;");
			startcmd("phpsh /var/run/restart_upwifistats.sh");
		}
		$i++;
	}
	$i=1;
	while ($i>0)
	{
		$uid = "BAND5G-1.".$i;
		$p = XNODE_getpathbytarget("", "phyinf", "uid", $uid, 0);
		if ($p=="") {$i=0; break;}
		$active=query($p."/active");
		if($active=="1")
		{
			startcmd("xmldbc -P /etc/services/WIFI/updatewifistats.php -V PHY_UID=".$uid." > /var/run/restart_upwifistats.sh;");
			startcmd("phpsh /var/run/restart_upwifistats.sh");
		}
		$i++;
	}	
	
	/*It should restart the WEBACCESS service when the WAN connection is completed.*/
	if($index==1 && isfile("/etc/services/WEBACCESS.php")==1)
	{
		startcmd("service WEBACCESS restart\n");
	}
	
	//==20121217 jack add for ipconflict==//
	infsvcs_pre_ipconflict();
	//==20121217 jack add for ipconflict==//
	
	return error(0);
}

function infsvcs_lan($index)
{
	$uid = "LAN-".$index;
	$cfg = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
	$sts = XNODE_getpathbytarget("/runtime", "inf", "uid", $uid, 0);
	if ($cfg=="" || $sts=="")
	{
		SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$uid.") not exist.");
		SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$uid.") not exist.");
		return error(9);
	}
	$addrtype = query($sts."/inet/addrtype");
	if ($addrtype=="ipv6" || $addrtype=="ppp6") $ipv6=1; else $ipv6=0;

	
	/* Tell everybody, we are going down. */
	stopcmd("event INFSVCS.".$uid.".DOWN");
	if ($ipv6>0)
	{
		stopcmd("service IP6TDEFCHAIN restart");
		stopcmd("service IP6TOBF restart");
				
		//+++ Jerry Kao, added for support ingress filtering (BCP 38).	
		stopcmd("service IP6TSMPSECURITY restart");		
		
		stopcmd("service MULTICAST restart");
	}
	else
	{
		stopcmd("service IPTDEFCHAIN restart");
		stopcmd("service MULTICAST restart");
	}

	infsvcs_setup($uid, $cfg, $sts, $ipv6);

	/* Update the routing tables */
	if ($ipv6>0)
	{
		
		//+++ Jerry Kao, added for support ingress filtering (BCP 38).					
		startcmd("service IP6TSMPSECURITY restart");
				
		startcmd("service IP6TOBF restart");
		startcmd("service IP6TDEFCHAIN restart");
	}
	else
	{
		startcmd("service MULTICAST restart");
		startcmd("service IPTDEFCHAIN restart");
	}
	startcmd("event INFSVCS.".$uid.".UP");
	
	startcmd("service LLD2 restart");	//20121219 jack add for LLTD==//
	return error(0);
}

function infsvcs_bridge($index)
{
	$uid = "BRIDGE-".$index;
	$cfg = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
	$sts = XNODE_getpathbytarget("/runtime","inf", "uid", $uid, 0);
	if ($cfg=="" || $sts=="")
	{
		SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$uid.") not exist.");
		SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$uid.") not exist.");
		return error(9);
	}
	$addrtype = query($sts."/inet/addrtype");
	if ($addrtype=="ipv6" || $addrtype=="ppp6") $ipv6=1; else $ipv6=0;

	/* Tell everybody, we are going down. */
	stopcmd("event INFSVCS.".$uid.".DOWN");
	stopcmd("event UPDATERESOLV");
	stopcmd("event INET.DISCONNECTED");

	if ($ipv6>0) stopcmd("service IP6TDEFCHAIN restart");
	else
	{
		stopcmd("service IPTDEFCHAIN restart");
		stopcmd("service MULTICAST restart");
	}

	infsvcs_setup($uid, $cfg, $sts, $ipv6);

	if ($ipv6>0) startcmd("service IP6TDEFCHAIN restart");
	else
	{
		startcmd("service IPTDEFCHAIN restart");
		startcmd("service MULTICAST restart");
	}
	startcmd("event UPDATERESOLV");
	startcmd("event INET.CONNECTED");
	startcmd("event INFSVCS.".$uid.".UP");
}
?>
