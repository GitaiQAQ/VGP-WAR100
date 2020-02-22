<?
/* fatlady is used to validate the configuration for the specific service.
 * 3 variables should be returned for the result:
 * FATLADY_result, FATLADY_node & FATLADY_message. */
include "/htdocs/phplib/trace.php";

function result($res, $node, $msg)
{
	$_GLOBALS["FATLADY_result"] = $res;
	$_GLOBALS["FATLADY_node"] = $node;
	$_GLOBALS["FATLADY_message"] = $msg;
	return $res;
}

function valid_mac($mac)
{
	if ($mac=="") return 0;
	$num = cut_count($mac, ":");
	if ($num!=6) return 0;
	$num--;
	while ($num>=0)
	{
		$tmp = cut($mac, $num, ':');
		if (isxdigit($tmp)==0) return 0;
		$num--;
	}
	return 1;
}

function verify_staticleases($b)
{
	return result("OK","","");
}

function verify_dhcps4($path)
{
	return verify_staticleases($path);
}

//////////////////////////////////////////////////////////////////////////////

/* The default max value. */
$max = query("/dhcps4/max");
$count = query($FATLADY_prefix."/dhcps4/count");
$seqno = query($FATLADY_prefix."/dhcps4/seqno");
if ($count>$max) $count=$max;
foreach($FATLADY_prefix."/dhcps4/entry")
{
	if ($InDeX > $count) break;
	$entry = $FATLADY_prefix."/dhcps4/entry:".$InDeX;
	$ret = verify_dhcps4($entry);
	if ($ret!="OK") break;
	if (query($entry."/uid")=="")
	{
		set($entry."/uid", "DHCPS4-".$seqno);
		$seqno++;
		set($FATLADY_prefix."/dhcps4/seqno", $seqno);
	}
}
if ($ret=="OK") set($FATLADY_prefix."/valid", "1");
?>
