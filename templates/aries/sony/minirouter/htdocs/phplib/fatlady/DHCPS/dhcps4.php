<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/phyinf.php";

function set_result($result, $node, $message)
{
	$_GLOBALS["FATLADY_result"] = $result;
	$_GLOBALS["FATLADY_node"]   = $node;
	$_GLOBALS["FATLADY_message"]= $message;
}

function check_dhcp_config($path, $inetp)
{
	/* get the info of related IP address and subnet mask */
	$_SVC	= XNODE_get_var("SERVICE_NAME");
	$_UID	= cut($_SVC, 1, ".");
	$ip		= query($inetp."/ipv4/ipaddr");
	$mask	= query($inetp."/ipv4/mask");

	anchor($path);
	$start_ip = query("start");
	$end_ip = query("end");
	TRACE_debug("FATLADY: DHCPS4: ip = ".$ip);
	TRACE_debug("FATLADY: DHCPS4: mask = ".$mask);
	TRACE_debug("FATLADY: DHCPS4: lease pool from ".$start_ip." to ".$end_ip);

	/* check lease range is not include the LAN IP */
	$lan_id = ipv4hostid($ip, $mask);


	/* check domain name */
	$domain = query("domain");
	if ($domain!="" && isdomain($domain)=="0")
	{
		set_result("FAILED",$path."/domain","Invalid domain name.");
		return;
	}


	/* check router */
	$router = query("router");
	if (ipv4networkid($router,32)=="" && $router!="")
	{
		set_result("FAILED",$path."/router","The input router address is invalid.");
		return;
	}

	/* check dns server */
	$cnt = query("dns/count");
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		if (ipv4networkid(query("dns/entry:".$i),32)=="")
		{
			set_result("FAILED",$path."/dns/entry:".$i,"The input DNS server address is invalid.");
			return;
		}
	}


	/* check staticleases */
	$_LANID   = cut($_UID, 1, "-");
	$p = XNODE_get_var("FATLADY_DHCPS_PATH");
	$dhcpp = XNODE_getpathbytarget($p."/dhcps4", "entry", "uid", "DHCPS4-".$_LANID, 0);

	$seqno = query($dhcpp."/staticleases/seqno");
	$p = $dhcpp."/staticleases/entry";
	$cnt = query($dhcpp."/staticleases/count");
	$i = 0;

	foreach ($p)
	{
		if ($InDeX > $cnt) break;
		$uid = query("uid");
		/* Check empty UID */
		if ($uid == "")
		{
			$uid = "STIP-".$seqno;
			set("uid", $uid);
			$seqno++;
		}
		/* Check duplicated UID */
		if ($$uid == "1")
		{
			set_result("FAILED", $p.":".$InDeX."/uid", "Duplicated UID - ".$uid);
	       	return;
    	}
    	$$uid = "1";

		/* Check empty hostname*/
   		$hostname = query("hostname");
	    if ($hostname == "" || isdomain($hostname)=="0")
		{
	        set_result("FAILED", $p.":".$InDeX."/hostname", "Invalid host name.");
    		return;
    	}

		$mac = query("macaddr");
	    if ($mac == "")
		{
	        set_result("FAILED", $p.":".$InDeX."/macaddr", I18N("h","There is no MAC address."));
			return;
	   	}

	    if (PHYINF_validmacaddr($mac) != 1)
	    {
	        set_result("FAILED", $p.":".$InDeX."/macaddr", I18N("h","This MAC address is invalid."));
	        return;
		}

		/* Check duplicate mac */
		$i2 = $InDeX;
		//TRACE_debug("DHCPS4[".$i2."]");
		while ($i2 < $cnt)
		{
			$i2++;
			$m2 = query($p.":".$i2."/macaddr");
			//TRACE_debug("DHCPS4:".$i2."-".$m2);
			if (tolower($mac) == tolower($m2))
			{
				set_result("FAILED", $p.":".$InDeX."/macaddr", "This MAC address has already been set for filtering.");
				return;
			}
		}

		/* check hostid is not out of the boundary */
		$hostid = query("hostid");
	    if ($hostid == 0 || $hostid >= ipv4maxhost($mask) || $hostid == $lan_id)
	    {
			set_result("FAILED", $p.":".$InDeX."/hostid", I18N("h","This IP address is invalid."));
	        return;
	    }

		/* repeat check */
		$rlt = 0;
	    $i = $InDeX + 1;
   		while ($i <= $cnt)
    	{
       		if (tolower($mac) == query($dhcpp."/staticleases/entry:".$i."/macaddr"))
        	{
           		set_result("FAILED", $dhcpp."/staticleases/entry:".$i."/macaddr", "This MAC address has already been set for filtering.");
            	$rlt = "-1";
            	break;
        	}

			if ($hostid == query($dhcpp."/staticleases/entry:".$i."/hostid"))
        	{
           		set_result("FAILED", $dhcpp."/staticleases/entry:".$i."/hostid", "Duplicated IP addresses.");
            	$rlt = "-1";
            	break;
        	}
        	$i++;
    	}
    	if ($rlt != "0") return;
    	set($p.":".$InDeX."/macaddr", tolower($mac));
	}
	set($dhcpp."/staticleases/seqno", $seqno);

	set_result("OK", "", "");
}

set_result("FAILED","","");

$path = XNODE_get_var("FATLADY_DHCPS_PATH");
if ($path=="")
	set_result("FAILED","","No XML document");
else
{
	$dhcp = query($path."/inf/dhcps4");
	if ($dhcp!="")
	{
		$dhcpentry = XNODE_getpathbytarget($path."/dhcps4", "entry", "uid", $dhcp, 0);
		if ($dhcpentry!="") check_dhcp_config($dhcpentry, $path."/inet/entry");
	}
	else
	{
		set_result("OK","","");
	}
}
?>
