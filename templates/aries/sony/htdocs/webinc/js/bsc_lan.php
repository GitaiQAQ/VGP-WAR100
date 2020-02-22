<?include "/htdocs/phplib/inet.php";?>
<?include "/htdocs/phplib/inf.php";?>

<script type="text/javascript">
function Page() {}
Page.prototype =
{
	//services: "INET.LAN-1,DHCPS4.LAN-1,RUNTIME.INF.LAN-1,WAN,INET.INF",
	services: "<?
		$layout = query("/runtime/device/layout");		
		if ($layout=="bridge")
			echo "INET.INF";
		else
			echo "INET.LAN-1,DHCPS4.LAN-1,RUNTIME.INF.LAN-1";
		?>",
	OnLoad: function()
	{
		SetDelayTime(500);	//add delay for event updatelease finished
		if (!this.rgmode)
		{
			OBJ("dhcpc_en").disabled = false;
			OBJ("broadcast").disabled = true;
		}
		else
		{
			OBJ("dhcpc_en").disabled = true;
			OBJ("broadcast").disabled = false;
		}
	},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			if (this.ipdirty)
			{
				Service("REBOOT", OBJ("ipaddr").value);
			}
			else
			{
				BODY.OnReload();
			}
			break;
		case "BUSY":
			BODY.ShowAlert("<?echo I18N("j","Someone is configuring the device, please try again later.");?>");
			break;
		case "HEDWIG":
			if (result.Get("/hedwig/result")=="FAILED")
			{
				BODY.ShowAlert(result.Get("/hedwig/message"));
			}
			break;
		case "PIGWIDGEON":
			BODY.ShowAlert(result.Get("/pigwidgeon/message"));
			break;
		}
		return true;
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		if(!this.rgmode)
		{
			if (!this.InitLAN_AP()) return false;
		}
		else
		{
			if (!this.InitLAN()) return false;
			if (!this.InitDHCPS()) return false;
		}
		return true;
	},
	PreSubmit: function()
	{
		if(!this.rgmode)
		{
			if (!this.PreLAN_AP()) return null;
		}
		else
		{
			if (!this.PreLAN()) return null;
			if (!this.PreDHCPS()) return null;
		}
		PXML.IgnoreModule("DEVICE.LAYOUT");
		PXML.IgnoreModule("RUNTIME.INF.LAN-1");
		return PXML.doc;
	},	
	IsDirty: function() {},
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	lanip: null,
	infp: null,
	inetp: null,
	inetp_runtime: null,
	mask: null,
	lanip_ap: null,
	infp_ap: null,
	inetp_ap: null,
	mask_ap: null,
	dhcps4: null,
	dhcps4_inet: null,
	leasep: null,
	ipdirty: false,
	cfg: null,
	g_edit: 0,
	g_table_index: 1,

	InitLAN: function()
	{
		var lan	= PXML.FindModule("INET.LAN-1");
		var inetuid = XG(lan+"/inf/inet");
		this.inetp = GPBT(lan+"/inet", "entry", "uid", inetuid, false);
		//==20121226 jack When ipconflict happen, will show the runtime code ip==//
		var inetuid_runtime = XG  (lan+"/inf/inet");
		var lan_runtime	= PXML.FindModule("RUNTIME.INF.LAN-1");
		
		this.inetp_runtime = GPBT(lan_runtime+"/runtime/inf", "inet", "uid", inetuid_runtime, false);
		
		if (!this.inetp_runtime)
		{
			BODY.ShowAlert("InitLAN_runtime() ERROR!!!");
			if (!this.inetp)
			{
				BODY.ShowAlert("InitLAN() ERROR!!!");
				return false;
			}
			this.inetp = GPBT(lan+"/inet", "entry", "uid", inetuid, false);
			this.inetp_runtime = this.inetp;
			
		}
		//==20121226 jack When ipconflict happen, will show the runtime code ip==//
		/*
		if (!this.inetp)
		{
			BODY.ShowAlert("InitLAN() ERROR!!!");
			return false;
		}
		if (XG(this.inetp+"/addrtype") == "ipv4")
		{
			var b = this.inetp+"/ipv4";
			this.lanip = XG(b+"/ipaddr");
			this.mask = XG(b+"/mask");//mask is 24
			OBJ("ipaddr").value	= this.lanip;
		}
		*/
		if (XG(this.inetp_runtime+"/addrtype") == "ipv4")
		{
			var b = this.inetp_runtime+"/ipv4";
			this.lanip = XG(b+"/ipaddr");
			this.mask = XG(b+"/mask");//mask is 24
			OBJ("ipaddr").value	= this.lanip;
		}
		
		return true;
	},
	
	InitLAN_AP: function()
	{
		var base = PXML.FindModule("INET.INF");
		this.infp_ap	= GPBT(base, "inf", "uid", "BRIDGE-1", false);
		this.inetp_ap	= GPBT(base+"/inet", "entry", "uid", XG(this.infp_ap+"/inet"), false);

		if (!this.inetp_ap)
		{
			BODY.ShowAlert("InitLAN_AP() ERROR!!!");
			return false;
		}
		
		if(XG(this.inetp_ap+"/ipv4/static") == "1")
		{
			OBJ("dhcpc_en").checked = false;
			if (XG(this.inetp_ap+"/addrtype") == "ipv4")
			{
				var b = this.inetp_ap+"/ipv4";
				this.lanip_ap = XG(b+"/ipaddr");
				this.mask_ap = XG(b+"/mask");//mask is 24
				OBJ("ipaddr").value	= this.lanip_ap;
			}
		}
		else
		{
			OBJ("dhcpc_en").checked = true;
		}	
		this.OnClickDHCPC_EN();
		
		return true;
	},
	
	PreLAN: function()
	{
		var lan = PXML.FindModule("INET.LAN-1");
		var b = this.inetp+"/ipv4";
		/*
		var base = PXML.FindModule("INET.INF");
		this.infp	= GPBT(base, "inf", "uid", "LAN-1", false);
		this.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.infp+"/inet"), false);
		var b = this.inetp_ap+"/ipv4";
		*/
		if (!SettingIP(b)) return false;
		if (!SettingMASK(b)) return false;
		
		if (COMM_EqBOOL(OBJ("ipaddr").getAttribute("modified"), true))
		{
			this.ipdirty = true;
		}
			
		if (this.ipdirty)
		{
			PXML.DelayActiveModule("INET.LAN-1", "3");
		}
		else
		{
			PXML.IgnoreModule("INET.LAN-1");
		}
		
		return true;
	},
	PreLAN_AP: function()
	{
		var base = PXML.FindModule("INET.INF");
		this.infp_ap	= GPBT(base, "inf", "uid", "BRIDGE-1", false);
		this.inetp_ap	= GPBT(base+"/inet", "entry", "uid", XG(this.infp_ap+"/inet"), false);
		var b = this.inetp_ap+"/ipv4";
		
		if (COMM_EqBOOL(OBJ("dhcpc_en").getAttribute("modified"), true))
		{
			if (OBJ("dhcpc_en").checked)
			{
				XS(this.inetp_ap+"/ipv4/static", "0");
			}
			else
			{
				XS(this.inetp_ap+"/ipv4/static", "1");
				if (COMM_EqBOOL(OBJ("ipaddr").getAttribute("modified"), false))
				{
					if (!SettingIP(b)) return false;
					if (!SettingMASK(b)) return false;
				}
			}
		}
		
		if (COMM_EqBOOL(OBJ("ipaddr").getAttribute("modified"), true))
		{
			if (!SettingIP(b)) return false;
			if (!SettingMASK(b)) return false;
			//this.ipdirty = true;
		}
		return true;
	},
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

		//sam_pan add
		OBJ("broadcast").value	= XG(this.dhcps4+"/broadcast");	
		
		//==20121206 jack add for broadcast only==//
		if(XG(this.dhcps4+"/broadcast") == "yes")
		{
			OBJ("broadcast").checked = true;
		}
		else
		{
			OBJ("broadcast").checked = false;	
		}
		//==20121206 jack add for broadcast only==//
		
		if (!this.leasep)	return true;	// in bridge mode, the value of this.leasep is null.
		
		return true;
	},
	PreDHCPS: function()
	{
		var lan = PXML.FindModule("DHCPS4.LAN-1");
		var ipaddr = COMM_IPv4NETWORK(OBJ("ipaddr").value, "24");

		XS(this.dhcps4_inet+"/ipv4/ipaddr", OBJ("ipaddr").value);
		XS(this.dhcps4_inet+"/ipv4/mask", this.mask);
		XS(this.dhcps4+"/broadcast", OBJ("broadcast").checked?"yes":"no");
		/*---set values to xml*/
		PXML.ActiveModule("DHCPS4.LAN-1");
		return true;
	},
	
	OnClickDHCPC_EN: function()
	{
		if (OBJ("dhcpc_en").checked)
		{
			OBJ("ipaddr").value = this.lanip_ap;
			OBJ("ipaddr").disabled = true;
		}
		else
		{
			OBJ("ipaddr").disabled = false;
		}
	},
	
}

function SettingIP(setting_path)
{
	var vals = OBJ("ipaddr").value.split(".");
	if (vals.length!=4)
	{
		BODY.ShowAlert("<?echo I18N("j","This IP address is invalid.");?>");
		return false;
	}
	for (var i=0; i<4; i++)
	{
		if (!TEMP_IsDigit(vals[i]) || vals[i]>255)
		{
			BODY.ShowAlert("<?echo I18N("j","This IP address is invalid.");?>");
			return false;
		}
	}
	XS(setting_path+"/ipaddr", OBJ("ipaddr").value);
	return true;
}
function SettingMASK(setting_path)
{
	var vals = OBJ("ipaddr").value.split(".");
	var setmaskvalue = 24;
	switch(vals[0] & 192) //0xC0
	{
		case 0://0x0
		case 64://0x0
		{
			setmaskvalue = 8;
			XS(setting_path+"/mask", setmaskvalue);
			break;
		}
		case 128://0x80
		{
			setmaskvalue = 16;
			XS(setting_path+"/mask", setmaskvalue);
			break;
		}
		case 192://0xC0
		{
			setmaskvalue = 24;
			XS(setting_path+"/mask", setmaskvalue);
			break;
		}
		default:
		{
			BODY.ShowAlert("Netmask cal error");
			return false;
		}
	}
	return true;
}
function SetDelayTime(millis)
{
	var date = new Date();
	var curDate = null;
	curDate = new Date();
	do { curDate = new Date(); }
	while(curDate-date < millis);
}


function Service(svc, ipaddr)
{	
	var banner = "<?echo I18N("j","Rebooting the router...");?>";
	var msgArray = ["<?echo I18N("j","If you changed the IP address of the router, you will need to change the IP address in your browser in order to access the setting screen again.");?>"];
	var delay = 10;
	var sec = <?echo query("/runtime/device/bootuptime");?> + delay;
	var url = null;
	var ajaxObj = GetAjaxObj("SERVICE");
	if (svc=="FRESET")		url = "http://192.168.11.1/index.php";
	else if (svc=="REBOOT")	url = "http://"+ipaddr+"/index.php";
	else					return false;
	ajaxObj.createRequest();
	ajaxObj.onCallback = function (xml)
	{
		ajaxObj.release();
		if (xml.Get("/report/result")!="OK")
		{
			BODY.ShowAlert("Internal ERROR!\nEVENT "+svc+": "+xml.Get("/report/message"));
		}
		else
		{
			
			var msgArray1 =
			[
				'<?echo I18N("j","If you changed the IP address of the router, you will need to change the IP address in your browser in order to access the setting screen again.");?>',
			];
			
			var msgArray2 =
			[
				'<?echo I18N("j","The router has finished rebooting. Please connect the PC to the router using wireless LAN.");?>',
				'<?echo I18N("j","Click the link below after connection to return to the router setting screen.");?>',
				'<a href="http://VGP-WAR100/" style="color:#0000ff;">http://VGP-WAR100/</a>'
			];
			var msgArray1_len = 1;
			BODY.ShowCountdown_OP('<?echo I18N("j","IP Address");?>', msgArray1, msgArray1_len, msgArray2 ,sec);
			//BODY.ShowCountdown(banner, msgArray, sec, url);
		}
	}
	ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxObj.sendRequest("service.cgi", "EVENT="+svc);
}
</script>
