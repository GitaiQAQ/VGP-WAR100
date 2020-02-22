<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";

fwrite("w", $START, "");
fwrite("w", $STOP, "");

$HOSTNAME = query("/device/hostname");
$INF = "br0";
$MDNS_CONF   = "/var/rendezvous.conf";
fwrite("w", $MDNS_CONF, "");

if (query("/device/mdnsresponder/enable")=="1")
{
	foreach ("/runtime/services/mdnsresponder/server")
	{
		if(strstr(query("uid"), "MDNSRESPONDER")!="") 
		{
			fwrite("a", $MDNS_CONF, query("srvname")."\n");
			fwrite("a", $MDNS_CONF, query("srvcfg")."\n");
			fwrite("a", $MDNS_CONF, query("port")."\n");
			fwrite("a", $MDNS_CONF, "\n");
		
	  }
	}
	fwrite("a", $START, "echo \"mdnsresponder server start !\" > /dev/console\n");
	//fwrite("a", $START, "HOSTNAME=`hostname`\n");
	//fwrite("a", $START, "if [ \"$HOSTNAME\" == \"\" ]; then hostname ".$HOSTNAME."; fi\n");
	fwrite("a", $START, "hostname ".$HOSTNAME."\n");
//jef add +   for support use shareport.local to access shareportmobile
	$web_file_access = query("/webaccess/enable");
	if($web_file_access == 1)
		fwrite("a", $START, "mDNSResponderPosix -b -i ".$INF." -f ".$MDNS_CONF." -e shareport \n");
	else
		fwrite("a", $START, "mDNSResponderPosix -b -i ".$INF." -f ".$MDNS_CONF." \n");
//jef add -
	fwrite("a", $STOP, "echo \"mdnsresponder server stop !\" > /dev/console\n");
	fwrite("a", $STOP, "killall -9 mDNSResponderPosix\n");
}
else
{
	fwrite("a", $START, "echo \"mdnsresponder server is disabled !\" > /dev/console\n");
	fwrite("a", $STOP, "echo \"mdnsresponder server is disabled !\" > /dev/console\n");
}

?>