<?include "/htdocs/phplib/inet.php";?>
<?include "/htdocs/phplib/inf.php";?>

<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "DHCPS4.LAN-1,RUNTIME.INF.LAN-1",
	OnLoad: function()
	{
		SetDelayTime(500);	//add delay for event updatelease finished
		BODY.CleanTable("leases_list");
	},
	OnUnload: function() {},
	OnSubmitCallback: function ()
	{
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		if (!this.InitDHCPS()) return false;
		return true;
	},
	PreSubmit: function()
	{
		if (!this.PreDHCPS()) return null;
		PXML.IgnoreModule("DEVICE.LAYOUT");
		PXML.IgnoreModule("RUNTIME.INF.LAN-1");
		return PXML.doc;
	},	
	IsDirty: function()	{},
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	dhcps4: null,
	dhcps4_inet: null,
	leasep: null,
	InitDHCPS: function()
	{				
		var svc = PXML.FindModule("DHCPS4.LAN-1");
		var inf1p = PXML.FindModule("RUNTIME.INF.LAN-1");
		if (!svc || !inf1p)
		{
			BODY.ShowAlert("InitDHCPS() ERROR !");
			return false;
		}
		this.dhcps4 = GPBT(svc+"/dhcps4", "entry", "uid", "DHCPS4-1", false);
		this.dhcps4_inet = svc + "/inet/entry";
		this.leasep = GPBT(inf1p+"/runtime", "inf", "uid", "LAN-1", false);		
		if (!this.dhcps4)
		{
			BODY.ShowAlert("InitDHCPS() ERROR !");
			return false;
		}
		this.leasep += "/dhcps4/leases";
					
		if (!this.leasep)	return true;	// in bridge mode, the value of this.leasep is null.
		entry = this.leasep+"/entry";
		cnt = XG(entry+"#");
		if (XG(svc+"/inf/dhcps4")!="")			// when the dhcp server is enabled show the dynamic dhcp clients list
		{
			for (var i=1; i<=cnt; i++)
			{
				var uid		= "DUMMY_"+i;
				var host	= XG(entry+":"+i+"/hostname");
				var ipaddr	= XG(entry+":"+i+"/ipaddr");
				var mac		= XG(entry+":"+i+"/macaddr");
				var expires	= XG(entry+":"+i+"/expire");
				if(parseInt(expires, 10) == 0)
				{
					continue;
				}
				if (parseInt(expires, 10) > 6000000)
				{
					expires = "Never";
				}
				else if (parseInt(expires, 10) < 60)
				{
					expires = "< 1 <?echo I18N("j","minute(s)");?>";
				}
				else
				{
					var time= COMM_SecToStr(expires);
					expires = "";

					if (time["day"]>0)
					{
						expires = time["day"]+" <?echo I18N("j",":");?> ";
					}
					if (time["hour"]>0)
					{
						expires += time["hour"]+" <?echo I18N("j",":");?> ";
					}
					if (time["min"]>0)
					{
						expires += time["min"];
					}
				}
				var data	= [host, ipaddr, mac, expires];
				var type	= ["text", "text", "text", "text"];
				if (expires != "Never")
				{								
					BODY.InjectTable("leases_list", uid, data, type);
				}
			}
		}
				
		return true;
	},
}

function SetDelayTime(millis)
{
	var date = new Date();
	var curDate = null;
	curDate = new Date();
	do { curDate = new Date(); }
	while(curDate-date < millis);
}

</script>
