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
	services: "DEVICE.LAYOUT,DEVICE.ACCOUNT,PHYINF.WAN-1,INET.BRIDGE-1,INET.INF,INET.WAN-1,WAN,REBOOT",
	OnLoad: function()
	{
		this.ShowCurrentStage();
	},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		
		if (COMM_Equal(OBJ("operation_mode_w").getAttribute("modified"), true) || OBJ("operation_mode_w").value == "auto")
		{
			if (OBJ("operation_mode_w").value == "ap")
			{
				var select_mode = '<?echo I18N("j","Rebooting in order to change to access point mode...");?>';
			}
			else if (OBJ("operation_mode_w").value == "rg")
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
			self.location.href = "./st_device.php";//20121012 jack change the front page
		}
		return true;
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		if (!this.Initial()) return false;
		if (!this.InitWANSettings()) return false;
		if (!this.InitAPmode()) return false;
		return true;
	},
	PreSubmit: function()
	{
		PXML.ActiveModule("DEVICE.ACCOUNT");
		PXML.CheckModule("INET.WAN-1", null, null, "ignore");

		PXML.CheckModule("PHYINF.WAN-1", null, null, "ignore");
		PXML.CheckModule("WAN", "ignore", "ignore", null);
		PXML.IgnoreModule("REBOOT");
		
		
		
		
		//==20121106 jack add for initial router mode show dhcp,pppoe..==============
		/*
		var layout = PXML.FindModule("DEVICE.LAYOUT");
		BODY.ShowAlert(XG(layout+"/device/layout"));
		BODY.ShowAlert(OBJ("operation_mode_w").value);
		if(OBJ("operation_mode_w").value != XG(layout+"/device/layout"))
		{
			this.PreAPmode();
		}
		*/
		
		if (COMM_Equal(OBJ("operation_mode_w").getAttribute("modified"), "true"))
			this.PreAPmode();
		
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	bootuptime: <?
		$bt=query("/runtime/device/bootuptime");
		if ($bt=="")	$bt=30;
		else			$bt=$bt+10;
		echo $bt;
	?>,
	passwdp: null,
	inet1p: null,
	inf1p: null,
	macaddrp: null,
	operatorp: null,
	//==for bridge/router mode changing==
	wan1:	{infp: null, inetp:null, phyinfp:null},
	br1:	{infp: null, inetp:null},
	bridge_addrtype: null,
	bridge_ipaddr: null,
	//==for bridge/router mode changing==
	stages: new Array ( "stage_interc","stage_ether_cfg","stage_passwd","stage_finish"),
	wanTypes: new Array ("DHCP", "PPPoE", "STATIC"),
	currentStage: 0,	// 0 ~ this.stages.length
	currentWanType: 0,	// 0 ~ this.wanTypes.length
	Initial: function()
	{
		this.passwdp = PXML.FindModule("DEVICE.ACCOUNT");
		if (!this.passwdp)
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		this.passwdp = GPBT(this.passwdp+"/device/account", "entry", "name", "admin", false);
		this.passwdp += "/password";
		OBJ("wiz_passwd").value = OBJ("wiz_passwd2").value = XG(this.passwdp);
		return true;
	},
	PrePasswd: function()
	{
		XS(this.passwdp, OBJ("wiz_passwd").value);
		return true;
	},
	InitWANSettings: function()
	{
		this.inet1p = PXML.FindModule("INET.WAN-1");
		var phyinfp = PXML.FindModule("PHYINF.WAN-1");
		if (!this.inet1p||!phyinfp)
		{
			BODY.ShowAlert("InitWANSettings() ERROR!!!");
			return false;
		}
		var inet1 = XG(this.inet1p+"/inf/inet");
		var eth = XG(phyinfp+"/inf/phyinf");
		this.inf1p = this.inet1p+"/inf";
		this.inet1p = GPBT(this.inet1p+"/inet", "entry", "uid", inet1, false);
		phyinfp = GPBT(phyinfp, "phyinf", "uid", eth, false);
		this.macaddrp = phyinfp+"/macaddr";
		this.operatorp += "/runtime/services/operator";
		this.GetWanType();
		SetRadioValue("wan_mode", this.wanTypes[this.currentWanType]);
		/////////////////////////// initial PPPv4 hidden nodes ///////////////////////////
		OBJ("ppp4_timeout").value	= IdleTime(XG(this.inet1p+"/ppp4/dialup/idletimeout"));
		OBJ("ppp4_mode").value		= XG(this.inet1p+"/ppp4/dialup/mode");
		OBJ("ppp4_mtu").value		= XG(this.inet1p+"/ppp4/mtu");
		/////////////////////////// initial DHCP settings ///////////////////////////
		OBJ("ipv4_mtu").value		= XG(this.inet1p+"/ipv4/mtu");
		/////////////////////////// initial PPPoE settings ///////////////////////////
		OBJ("wiz_pppoe_ipaddr").value	= ResAddress(XG(this.inet1p+"/ppp4/ipaddr"));
		OBJ("wiz_pppoe_usr").value		= XG(this.inet1p+"/ppp4/username");
		OBJ("wiz_pppoe_passwd").value	= XG(this.inet1p+"/ppp4/password");
		/////////////////////////// initial STATIC IP settings ///////////////////////////
		OBJ("wiz_static_ipaddr").value	= ResAddress(XG(this.inet1p+"/ipv4/ipaddr"));
		OBJ("wiz_static_mask").value	= COMM_IPv4INT2MASK(XG(this.inet1p+"/ipv4/mask"));
		OBJ("wiz_static_gw").value		= ResAddress(XG(this.inet1p+"/ipv4/gateway"));
		
		//==20130103 jack add dns==//
		OBJ("wiz_static_dns1").value	= ResAddress(XG(this.inet1p+"/ipv4/dns/entry:1"));
		OBJ("wiz_static_dns2").value	= ResAddress(XG(this.inet1p+"/ipv4/dns/entry:2"));
		
		
		if (XG(this.inet1p+"/ppp4/static")=="1")
		{
			document.getElementsByName("wiz_pppoe_conn_mode")[1].checked = true;
		}
		else
		{
			document.getElementsByName("wiz_pppoe_conn_mode")[0].checked = true;
		}
		this.OnChangePPPoEMode();
		return true;
	},
	PreWANSettings: function()
	{
		var type = GetRadioValue("wan_mode");
		XD(this.inet1p+"/ipv4");
		XD(this.inet1p+"/ppp4");
		XS(this.inf1p+"/lowerlayer", "");
		XS(this.inf1p+"/upperlayer", "");
		XS(this.inf1p+"/child", "");
		var addrtype = XG(this.inet1p+"/addrtype");
		switch (type)
		{

		case "DHCP":
			XS(this.inet1p+"/ipv4/dhcpplus/enable", "0");
			/////////////////////////// prepare DHCP settings ///////////////////////////
			XS(this.inet1p+"/addrtype", "ipv4");
			XS(this.inet1p+"/ipv4/static", 0);
			XS(this.inet1p+"/ipv4/mtu", OBJ("ipv4_mtu").value);
			break;
		case "PPPoE":
			/////////////////////////// prepare PPPoE settings ///////////////////////////
			var dynamic_pppoe = document.getElementsByName("wiz_pppoe_conn_mode")[0].checked ? true: false;
			XS(this.inet1p+"/addrtype", "ppp4");
			XS(this.inet1p+"/ppp4/over", "eth");
			XS(this.inet1p+"/ppp4/static", document.getElementsByName("wiz_pppoe_conn_mode")[0].checked ? 0:1);
			if (!dynamic_pppoe)	XS(this.inet1p+"/ppp4/ipaddr", OBJ("wiz_pppoe_ipaddr").value);
			XS(this.inet1p+"/ppp4/username", OBJ("wiz_pppoe_usr").value);
			XS(this.inet1p+"/ppp4/password", OBJ("wiz_pppoe_passwd").value);
			break;
		case "STATIC":
			/////////////////////////// prepare STATIC IP settings ///////////////////////////
			XS(this.inet1p+"/addrtype",		"ipv4");
			XS(this.inet1p+"/ipv4/static",	1);
			XS(this.inet1p+"/ipv4/ipaddr",	OBJ("wiz_static_ipaddr").value);
			XS(this.inet1p+"/ipv4/mask",	COMM_IPv4MASK2INT(OBJ("wiz_static_mask").value));
			XS(this.inet1p+"/ipv4/gateway",	OBJ("wiz_static_gw").value);
			XS(this.inet1p+"/ipv4/mtu",		OBJ("ipv4_mtu").value);
			//==20130103 jack add DNS==//
			SetDNSAddress(this.inet1p+"/ipv4/dns", OBJ("wiz_static_dns1").value, OBJ("wiz_static_dns2").value);
			break;
		}
		if (type=="DHCP"||type=="STATIC")
		{
		
		}
		else
		{
			/////////////////////////// prepare PPPv4 hidden nodes ///////////////////////////
			XS(this.inet1p+"/ppp4/dialup/idletimeout", (OBJ("ppp4_timeout").value=="0") ? 5:OBJ("ppp4_timeout").value);
			XS(this.inet1p+"/ppp4/dialup/mode", (OBJ("ppp4_mode").value=="") ? "ondemand": OBJ("ppp4_mode").value);
			if ((type!="PPPoE" /*&& type!="R_PPPoE"*/) && ( OBJ("ppp4_mtu").value < 576 || OBJ("ppp4_mtu").value > 1400 ) ) XS(this.inet1p+"/ppp4/mtu", "1400");  
			else XS(this.inet1p+"/ppp4/mtu", OBJ("ppp4_mtu").value);
		}

		return true;
	},
	//==20121106 jack add for operation mode=========
	InitAPmode: function()
	{
		var base = PXML.FindModule("INET.INF");
		
		this.wan1.infp	= GPBT(base, "inf", "uid", "WAN-1", false);
		this.wan1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.wan1.infp+"/inet"), false);
		var b = PXML.FindModule("PHYINF.WAN-1");
		this.wan1.phyinfp = GPBT(b, "phyinf", "uid", XG(b+"/inf/phyinf"), false);
		
		this.br1.infp	= GPBT(base, "inf", "uid", "BRIDGE-1", false);
		this.br1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.br1.infp+"/inet"), false);
		
		var layout = PXML.FindModule("DEVICE.LAYOUT");
		if (!layout) { alert("InitLayout ERROR !"); return false; }
		
		//==20121106 jack add for initial router mode show dhcp,pppoe..==============
		if(XG(layout+"/device/layout") == "bridge")
		{
			COMM_SetSelectValue(OBJ("operation_mode_w"), "ap");
			OBJ("RGmode_type").style.display = "none";
			OBJ("RGmode_button").style.display = "block";
		}
		else if(XG(layout+"/device/layout") == "router")
		{
			COMM_SetSelectValue(OBJ("operation_mode_w"), "rg");
			OBJ("RGmode_type").style.display = "block";
			OBJ("RGmode_button").style.display = "none";
		}
		else
		{
			COMM_SetSelectValue(OBJ("operation_mode_w"), "auto");
			OBJ("RGmode_type").style.display = "none";
			OBJ("RGmode_button").style.display = "block";
		}
		//==20121106 jack add for initial router mode show dhcp,pppoe..==============
		return true;
	},
	PreAPmode: function()
	{
	/*
		var layout = PXML.FindModule("DEVICE.LAYOUT")+"/device/layout";
	
		PXML.ActiveModule("DEVICE.LAYOUT");
		PXML.CheckModule("INET.BRIDGE-1", "ignore", "ignore", null);
	
		if (OBJ("operation_mode_w").value == "ap")
		{
			//router -> bridge mode 
			XS(layout, "bridge");
			this.bridge_addrtype = "dhcp";
			// If WAN-1 uses static IP address, use the IP as the bridge's IP. 
			if (XG(this.wan1.inetp+"/addrtype")==="ipv4" && XG(this.wan1.inetp+"/ipv4/static")==="1")
			{
				XS(this.br1.infp+"/previous/inet", XG(this.br1.infp+"/inet"));
				XS(this.br1.infp+"/inet", XG(this.wan1.infp+"/inet"));
				this.bridge_addrtype = "static";
				this.bridge_ipaddr = XG(this.wan1.inetp+"/ipv4/ipaddr");
			}
			// ignore other services 
	
			return PXML.doc;
		}
		*/
		
		var layout = PXML.FindModule("DEVICE.LAYOUT")+"/device/layout";
		//var cnt;
		
		PXML.ActiveModule("DEVICE.LAYOUT");
		PXML.CheckModule("INET.BRIDGE-1", "ignore", "ignore", null);
		if (OBJ("operation_mode_w").value == "ap")
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
		else if (OBJ("operation_mode_w").value == "rg")
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
	},
	
	
	//==20121106 jack add for operation mode=========
	ShowCurrentStage: function()
	{
		var i = 0;
		var type = "";
		for (i=0; i<this.wanTypes.length; i++)
		{
			type = this.wanTypes[i];
			OBJ(type).style.display = "none";
		}
		for (i=0; i<this.stages.length; i++)
		{
			if (i==this.currentStage)
			{
				OBJ(this.stages[i]).style.display = "block";
				if (this.stages[this.currentStage]=="stage_ether_cfg")
				{
					type = this.wanTypes[this.currentWanType];
					OBJ(type).style.display = "block";
				}
			}
			else
			{
				OBJ(this.stages[i]).style.display = "none";
			}
		}
		
		
		if (this.currentStage==0)
		{
			SetButtonDisabled("b_pre", true);
			SetButtonDisplayNone("b_pre", "none");
		}
		else
		{
			SetButtonDisabled("b_pre", false);
			SetButtonDisplayNone("b_pre", "");
		}
		
		if (this.currentStage==this.stages.length-1)
		{
			SetButtonDisabled("b_next", true);
			SetButtonDisabled("b_send", false);
			SetButtonDisplayNone("b_next", "none");
			SetButtonDisplayNone("b_send", "");
		}
		else
		{
			SetButtonDisabled("b_next", false);
			SetButtonDisabled("b_send", true);
			SetButtonDisplayNone("b_next", "");
			SetButtonDisplayNone("b_send", "none");
		}
	},
	SetStage: function(offset)
	{
		var length = this.stages.length;
		switch (offset)
		{
		case 1:
			if (this.currentStage < length-1)
				this.currentStage += 1;
			break;
		case -1:
			if (this.currentStage > 0)
				this.currentStage -= 1;
		}
	},
	OnClickPre: function()
	{
		var stage = this.stages[this.currentStage];
		if(stage == "stage_passwd" && (OBJ("operation_mode_w").value!="rg" || GetRadioValue("wan_mode")=="DHCP"))  
		{
			this.SetStage(-1);
			this.SetStage(-1);
			this.ShowCurrentStage();
		}
		else
		{
			this.SetStage(-1);
			this.ShowCurrentStage();
		}
	},
	OnClickNext: function()
	{
		var stage = this.stages[this.currentStage];

		if (stage == "stage_passwd")
		{
			for(var i=0;i < OBJ("wiz_passwd").value.length;i++)
			{
				if (OBJ("wiz_passwd").value.charCodeAt(i) > 256) //avoid holomorphic word
				{ 
					BODY.ShowAlert("<?echo I18N("j","This password is invalid.");?>");
					return false;
				}
			}			
			if (OBJ("wiz_passwd").value!=OBJ("wiz_passwd2").value)
			{
				BODY.ShowAlert("<?echo I18N("j","The entered password and re-entered password do not match. Please enter the same password.");?>");
				return false;
			}
			this.PrePasswd();
			CheckAccount();
		}
		else if (stage == "stage_ether_cfg")
		{
			this.PreWANSettings();
			var type = this.wanTypes[this.currentWanType];
			CheckWANSettings(type);
		}
		//==20121106 jack add operation mode=====
		else if(stage == "stage_interc" && (OBJ("operation_mode_w").value!="rg" || GetRadioValue("wan_mode")=="DHCP"))
		{
			if(GetRadioValue("wan_mode")=="DHCP")
			{
				this.PreWANSettings();
			}
			this.SetStage(1);
			this.SetStage(1);
			this.ShowCurrentStage();
		}
		//==20121106 jack add operation mode=====
		else
		{
			this.SetStage(1);
			this.ShowCurrentStage();
		}
	},
	OnClickCancel: function()
	{
		if (!COMM_IsDirty(false)||confirm("<?echo I18N("j","Do you want to abandon all changes you made?");?>"))
			self.location.href = "./st_device.php";//20121012 jack change front page
	},
	OnChangeWanTypeAuto: function(wantype)
	{
		SetRadioValue("wan_mode", wantype);
		this.OnChangeWanType(wantype);
	},	
	OnChangeWanType: function(type)
	{
		for (var i=0; i<this.wanTypes.length; i++)
		{
			//==20121003 jack add for hidden button===
			if (this.wanTypes[i]==type)
				this.currentWanType = i;
			//==20121003 jack add for hidden button===
		}
	},
	GetWanType: function()
	{
		var addrtype = XG(this.inet1p+"/addrtype");
		var type = null;
		switch (addrtype)
		{
		case "ipv4":
			if (XG(this.inet1p+"/ipv4/static")=="0")
			{
				type = "DHCP";
			}
			else
				type = "STATIC";
			break;
		case "ppp4":
		case "ppp10":
			if (XG(this.inet1p+"/ppp4/over")=="eth")
			{
					type = "PPPoE";
			}
			break;
		default:
			BODY.ShowAlert("Internal Error!!");
		}

		for (var i=0; i<this.wanTypes.length; i++)
		{
			if (this.wanTypes[i]==type)	this.currentWanType = i;
		}
	},
	OnChangePPPoEMode: function()
	{
		var disable = document.getElementsByName("wiz_pppoe_conn_mode")[0].checked ? true: false;
		OBJ("wiz_pppoe_ipaddr").disabled = disable;
	},
	
	//==20121106 jack add for operation mode==
	OnChangeAPMode_w: function()
	{
		switch (OBJ("operation_mode_w").value)
		{
			case "ap":
				OBJ("RGmode_type").style.display = "none";
				OBJ("RGmode_button").style.display = "block";
				break;
			case "rg":
				OBJ("RGmode_type").style.display = "block";
				OBJ("RGmode_button").style.display = "none";
				break;
			case "auto":
				OBJ("RGmode_type").style.display = "none";
				OBJ("RGmode_button").style.display = "block";
				break;
		}
	},
	//==20121106 jack add for operation mode==
	
	
}

function SetButtonDisabled(name, disable)
{
	var button = document.getElementsByName(name);
	for (i=0; i<button.length; i++)
	{
		button[i].disabled = disable;
	}
}
function SetButtonDisplayNone(name, display)
{
	var button = document.getElementsByName(name);
	for (i=0; i<button.length; i++)
	{
		button[i].style.display = display;
	}
}
function GetRadioValue(name)
{
	var radio = document.getElementsByName(name);
	var value = null;
	for (i=0; i<radio.length; i++)
	{
		if (radio[i].checked)	return radio[i].value;
	}
}
function SetRadioValue(name, value)
{
	var radio = document.getElementsByName(name);
	for (i=0; i<radio.length; i++)
	{
		if (radio[i].value==value)	radio[i].checked = true;
	}
}
function ResAddress(address)
{
	if (address=="")
		return "0.0.0.0";
	else if (address=="0.0.0.0")
		return "";
	else
		return address;
}
function SetDNSAddress(path, dns1, dns2)
{
	var cnt = 0;
	var dns = new Array (false, false);
	if (dns1!="0.0.0.0"&&dns1!="") {dns[0] = true; cnt++;}
	if (dns2!="0.0.0.0"&&dns2!="") {dns[1] = true; cnt++;}
	XS(path+"/count", cnt);
	if (dns[0]) XS(path+"/entry", dns1);
	if (dns[1]) XS(path+"/entry:2", dns2);
}

function CheckWANSettings(type)
{
	PXML.IgnoreModule("DEVICE.ACCOUNT");
	PXML.IgnoreModule("WAN");
	PXML.CheckModule("INET.WAN-1", null, "ignore", "ignore");
	switch (type)
	{
	case "DHCP":
		PXML.CheckModule("PHYINF.WAN-1", null, "ignore", "ignore");
		break;
	case "PPPoE":
		break; //hendry, we for PPPoE, don't have verify password right now. So we omit password checking.!!
	case "STATIC":
		if (OBJ("wiz_static_dns1").value==="" || OBJ("wiz_static_dns1").value==="0.0.0.0")
		{
			BODY.ShowAlert("<?echo I18N("j","This DNS address is invalid.");?>");
			return false;
		}
		break;
	}

	AUTH.UpdateTimeout();
	COMM_CallHedwig(PXML.doc, 
		function (xml)
		{
			switch (xml.Get("/hedwig/result"))
			{
			case "OK":
				PAGE.SetStage(1);
				PAGE.ShowCurrentStage();
				break;
			case "FAILED":
				BODY.ShowAlert(xml.Get("/hedwig/message"));
				break;
			}
		}
	);
}

function CheckAccount()
{
	PXML.CheckModule("DEVICE.ACCOUNT", null, "ignore", "ignore");
	PXML.IgnoreModule("PHYINF.WAN-1");
	PXML.IgnoreModule("WAN");	
	PXML.IgnoreModule("INET.WAN-1");

	AUTH.UpdateTimeout();
	COMM_CallHedwig(PXML.doc, 
		function (xml)
		{
			switch (xml.Get("/hedwig/result"))
			{
			case "OK":
				PAGE.SetStage(1);
				PAGE.ShowCurrentStage();			
				break;
			case "FAILED":
				BODY.ShowAlert(xml.Get("/hedwig/message"));
				break;
			}
		}
	);
}



function IdleTime(value)
{
	if (value=="")
		return "0";
	else
		return parseInt(value, 10);
}
</script>
