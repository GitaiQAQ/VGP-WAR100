<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";

function set_result($result, $node, $message)
{
	$_GLOBALS["FATLADY_result"]	= $result;
	$_GLOBALS["FATLADY_node"]	= $node;
	$_GLOBALS["FATLADY_message"]= $message;
}

function check_ppp4($path)
{
	include "/htdocs/webinc/feature.php";
	anchor($path);

	$over = query("over");
	if ($over != "eth" && $over != "pptp" && $over != "l2tp" && $over != "tty")
	{
		/* Internal error, no i18n. */
		set_result("FAILED", $path."/ipaddr", "Illegal value for over : ".$over);
		return;
	}

	/* IP address */
	$static = query("static");
	if ($static == "1")
	{
		$ipaddr = query("ipaddr");
		if (INET_validv4addr($ipaddr)==0)
		{
			set_result("FAILED",$path."/ipaddr",I18N("h","This IP address is invalid."));
			return;
		}
	}
	else
	{
		/* if static is not 1, it should be 0. */
		set("static", "0");
		del("ipaddr");
	}

	/* DNS */
	$cnt = query("dns/count");
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query("dns/entry:".$i);
		if (INET_validv4addr($value)==0)
		{
			set_result("FAILED",$path."/dns:".$i, I18N("h","This DNS address is invalid."));
			return;
		}
		set("dns/entry:".$i, INET_addr_strip0($value));
		
		if ($i > 1)
		{
			$j = $i - 1;
			$k = 0;
			while ($k < $j)
			{
				$k++;
				$dns = query("dns/entry:".$k);
				if($value == $dns)
				{
					set_result("FAILED", $path."/dns/entry:2", I18N("h","Secondary DNS server should not be the same as the primary DNS server."));
					return;
				}
			}
		}				
	}

	/* MTU/MRU */
	$mtu = query("mtu");
	if ($mtu != "")
	{
		if (isdigit($mtu)=="0")
		{
			set_result("FAILED",$path."/mtu",
				I18N("h","This MTU value is invalid."));
			return;
		}
		if ($mtu < 576 && $FEATURE_NOIPV6==1)
		{
			set_result("FAILED",$path."/mtu",
				I18N("h","The MTU value is too small. Valid values are 576-1492."));
			return;
		}
		else if ($mtu > 1492)
		{
            if($over == "tty")
            {
                if($mtu >1500)
                {
					set_result("FAILED",$path."/mtu","The MTU value is too large, the valid value for 3G is 576 ~ 1500.");
                    return;
                }
            }
            else
            {	
				set_result("FAILED",$path."/mtu",I18N("h","The MTU value is too large. Valid values are 576-1492."));
                return;
            }
		}
		$mtu = $mtu + 1 - 1; /* convert to number */
		set("mtu", $mtu);
	}

	/* User Name & Password */
	if (query("username")=="" && $over != "tty")
	{
		set_result("FAILED",$path."/username",I18N("h","Enter a username."));
		return;
	}
	/* dialup */
	$mode = query("dialup/mode");
	if ($mode != "auto" && $mode != "manual" && $mode != "ondemand")
	{
		/* no i18n */
		set_result("FAILED",$path."/dialup/mode","Invalid value for dial up mode - ".$mode);
		return;
	}
	$tout = query("dialup/idletimeout");
	if ($tout != "")
	{
		if (isdigit($tout)=="0" || $tout < 0 || $tout >= 10000)
		{
			set_result("FAILED",$path."/dialup/mode",
				I18N("h","The idle timeout value is invalid. Valid values are 0-9999."));
			return;
		}
	}

	if ($over == "eth")
	{
		/* should check service name & ac name here. */
	}

	set_result("OK","","");
}

TRACE_debug("FATLADY: INET: inetentry=[".$_GLOBALS["FATLADY_INET_ENTRY"]."]");
set_result("FAILED","","");
if ($_GLOBALS["FATLADY_INET_ENTRY"]=="") set_result("FAILED","","No XML document");
else check_ppp4($_GLOBALS["FATLADY_INET_ENTRY"]."/ppp4");
?>
