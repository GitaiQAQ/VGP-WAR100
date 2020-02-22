<?include "/htdocs/phplib/inet.php";?>
<?include "/htdocs/phplib/inf.php";?>
<?
	$inet = INF_getinfinfo("LAN-1", "inet");
	$ipaddr = INET_getinetinfo($inet, "ipv4/ipaddr");
?>
<script type="text/javascript">

function Page() {}
Page.prototype =
{
	services: "DEVICE.LAYOUT,INET.BRIDGE-1,INET.INF,REBOOT",
	OnLoad: function() {},
	OnUnload: function() {},
	dhcpc4: null,
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			
			if (COMM_Equal(OBJ("operation_mode").getAttribute("modified"), true))
			{
			
				if (OBJ("operation_mode").value == "ap")
				{
					var select_mode = '<?echo I18N("j","Rebooting in order to change to access point mode...");?>';
				}
				else if (OBJ("operation_mode").value == "rg")
				{
					var select_mode = '<?echo I18N("j","Rebooting in order to change to router mode...");?>';
				}
				else
				{
					var select_mode = '<?echo I18N("j","Rebooting in order to change to auto-detect mode...");?>';
				}
				
				
				var msgArray1 =
				[
					select_mode,
					'<?echo I18N("j","Please wait...");?>',
				];
				
				var msgArray2 =
				[
					'<?echo I18N("j","The router has finished rebooting. Please connect the PC to the router using wireless LAN.");?>',
					'<?echo I18N("j","Click the link below after connection to return to the router setting screen.");?>',
					'<a href="http://VGP-WAR100/" style="color:#0000ff;">http://VGP-WAR100/</a>'
				];
				var msgArray1_len = 2;
				BODY.ShowCountdown_OP('<?echo I18N("j","Change the Operation Mode");?>', msgArray1,msgArray1_len, msgArray2 ,this.bootuptime);
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
			BODY.ShowAlert(result.Get("/hedwig/message"));
			break;
		case "PIGWIDGEON":
			if (result.Get("/pigwidgeon/message")==="no power")
			{
				AUTH.Logout();
				BODY.ShowLogin();
			}
			else
			{
				BODY.ShowAlert(result.Get("/pigwidgeon/message"));
			}
			break;
		}
		return true;
	},
	InitValue: function(xml)
	{
		this.defaultCFGXML = xml;
		PXML.doc = xml;

		/* init the WAN-# & br-# obj */
		var base = PXML.FindModule("INET.INF");
		
		this.wan1.infp	= GPBT(base, "inf", "uid", "WAN-1", false);
		this.wan1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.wan1.infp+"/inet"), false);

		this.br1.infp	= GPBT(base, "inf", "uid", "BRIDGE-1", false);
		this.br1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.br1.infp+"/inet"), false);

		if (!base) { alert("InitValue ERROR!"); return false; }

		var layout = PXML.FindModule("DEVICE.LAYOUT");
		if (!layout) { alert("InitLayout ERROR !"); return false; }

		//==20121031 jack add==============
		if(XG(layout+"/device/layout") == "bridge")
			COMM_SetSelectValue(OBJ("operation_mode"), "ap");
		else if(XG(layout+"/device/layout") == "router")
			COMM_SetSelectValue(OBJ("operation_mode"), "rg");
		else
			COMM_SetSelectValue(OBJ("operation_mode"), "auto");
		//==20121031 jack add==============
		
		return true;
	},
	PreSubmit: function()
	{
		/* disable all modules */
		PXML.IgnoreModule("DEVICE.LAYOUT");
		PXML.IgnoreModule("INET.BRIDGE-1");
		PXML.CheckModule("INET.INF");
		
		
		/* router/bridge mode setting */
		if (COMM_Equal(OBJ("operation_mode").getAttribute("modified"), "true"))
		{
			var layout = PXML.FindModule("DEVICE.LAYOUT")+"/device/layout";
			var cnt;
			
			PXML.ActiveModule("DEVICE.LAYOUT");
			PXML.CheckModule("INET.BRIDGE-1", "ignore", "ignore", null);
			
			
			if (OBJ("operation_mode").value == "ap")
			{
				// router -> bridge mode
				XS(layout, "bridge");
				
				this.bridge_addrtype = "dhcp";
				// If WAN-1 uses static IP address, use the IP as the bridge's IP. 
				if (XG(this.wan1.inetp+"/addrtype")==="ipv4" && XG(this.wan1.inetp+"/ipv4/static")==="1")
				{
					XS(this.br1.infp+"/previous/inet", XG(this.br1.infp+"/inet"));
					//XS(this.br1.infp+"/inet", XG(this.wan1.infp+"/inet"));
					
					XS(this.br1.inetp+"/addrtype", XG(this.wan1.inetp+"/addrtype"));
					XS(this.br1.inetp+"/ipv4/static", XG(this.wan1.inetp+"/ipv4/static"));
					XS(this.br1.inetp+"/ipv4/mask", XG(this.wan1.inetp+"/ipv4/mask"));
					XS(this.br1.inetp+"/ipv4/gateway", XG(this.wan1.inetp+"/ipv4/gateway"));
					XS(this.br1.inetp+"/ipv4/mtu", XG(this.wan1.inetp+"/ipv4/mtu"));
					XS(this.br1.inetp+"/ipv4/dns/count", XG(this.wan1.inetp+"/ipv4/dns/count"));
					XS(this.br1.inetp+"/ipv4/ipaddr", XG(this.wan1.inetp+"/ipv4/ipaddr"));
		
		
					if(XG(this.wan1.inetp+"/ipv4/dns/count")=="1")
					{
						XS(this.br1.inetp+"/ipv4/dns/entry", XG(this.wan1.inetp+"/ipv4/dns/entry"));
					}
					else if(XG(this.wan1.inetp+"/ipv4/dns/count")=="2")
					{
						XS(this.br1.inetp+"/ipv4/dns/entry", XG(this.wan1.inetp+"/ipv4/dns/entry"));
						XS(this.br1.inetp+"/ipv4/dns/entry:2", XG(this.wan1.inetp+"/ipv4/dns/entry:2"));
					}
					else
					{
						BODY.ShowAlert("<?echo I18N("j","This DNS address is invalid.");?>");
					}
					
					this.bridge_addrtype = "static";
					this.bridge_ipaddr = XG(this.wan1.inetp+"/ipv4/ipaddr");
				}
				else//set DHCPC
				{
					XS(this.br1.inetp+"/ipv4/static","0");	
				}
			}
			else if (OBJ("operation_mode").value == "rg")
			{
				// bridge -> router 
				XS(layout, "router");

				// restore the inet of bridge
				if (XG(this.br1.infp+"/previous/inet")!=="")
				{
					XS(this.br1.infp+"/inet", XG(this.br1.infp+"/previous/inet"));
					XD(this.br1.infp+"/previous/inet");
				}
			}
			else
			{
				XS(layout, "auto");
			}
		}
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////
	bootuptime: <?

		$bt=query("/runtime/device/bootuptime");
		if ($bt=="")	$bt=30;
		else			$bt=$bt+10;
		echo $bt;

	?>,
	defaultCFGXML: null,
	wan1:	{infp: null, inetp:null, phyinfp:null},
	br1:	{infp: null, inetp:null},
	// for bridge/router mode changing
	bridge_addrtype: null,
	bridge_ipaddr: null,
}

</script>
