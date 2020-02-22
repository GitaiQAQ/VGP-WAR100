<?include "/htdocs/phplib/inet.php";?>
<?include "/htdocs/phplib/inf.php";?>
<?
	$inet = INF_getinfinfo("LAN-1", "inet");
	$ipaddr = INET_getinetinfo($inet, "ipv4/ipaddr");
?>
<script type="text/javascript">
//==20121219 jack add for PPPoE connect button==//
var EventName=null;
function SendEvent(str)
{
	var ajaxObj = GetAjaxObj(str);
	if (EventName != null) return;

	EventName = str;
	ajaxObj.createRequest();
	ajaxObj.onCallback = function (xml)
	{
		ajaxObj.release();
		EventName = null;				
	}
	ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxObj.sendRequest("service.cgi", "EVENT="+EventName);
}
function WAN1PPPDIALUP()	{ SendEvent("WAN-1.PPP.DIALUP"); }
function WAN1PPPHANGUP()	{ SendEvent("WAN-1.PPP.HANGUP"); }
function WAN1COMBODIALUP()	{ SendEvent("WAN-1.COMBO.DIALUP"); }
function WAN1COMBOHANGUP()	{ SendEvent("WAN-1.COMBO.HANGUP"); }
//==20121219 jack add for PPPoE connect button==//

function Page() {}
Page.prototype =
{
	services: "DEVICE.LAYOUT,PHYINF.WAN-1,INET.INF,WAN,DHCPC4.WAN,REBOOT,INET.WAN-1,RUNTIME.INF.WAN-1,RUNTIME.PHYINF",
	OnLoad: function()
	{
		if (!this.rgmode)
		{
			BODY.DisableCfgElements(true);
		}
	},
	OnUnload: function() {},
	dhcpc4: null,
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			
			var title_countdown = ["<?echo I18N("j","Clone Your PC\'s MAC Address");?>"];	
			
			if(COMM_Equal(OBJ("ipv4_macaddr").getAttribute("modified"), true) || COMM_Equal(OBJ("ppp4_macaddr").getAttribute("modified"), true))
			{
				var msgArray = ['<?echo I18N("j","Copying the address. Please wait.");?>...'];	
				BODY.ShowCountdown(title_countdown, msgArray, this.bootuptime, "http://<?echo $_SERVER['HTTP_HOST'];?>/bsc_wan.php");
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
		var b = PXML.FindModule("PHYINF.WAN-1");
		this.wan1.phyinfp = GPBT(b, "phyinf", "uid", XG(b+"/inf/phyinf"), false);
		
		if (!base) { alert("InitValue ERROR!"); return false; }

		var layout = PXML.FindModule("DEVICE.LAYOUT");
		if (!layout) { alert("InitLayout ERROR !"); return false; }

		/* init wan type */
		var wan1addrtype = XG(this.wan1.inetp+"/addrtype");
		if (wan1addrtype === "ipv4")
		{
			if (XG(this.wan1.inetp+"/ipv4/static")==="1")	COMM_SetSelectValue(OBJ("wan_ip_mode"), "static");
			else
			{
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "dhcp");
			}

			if (XG(this.wan1.inetp+"/ipv4/ipv4in6/mode")!="")	
				COMM_SetSelectValue(OBJ("wan_ip_mode"), XG(this.wan1.inetp+"/ipv4/ipv4in6/mode"));
		}
		else if (wan1addrtype === "ppp4")
		{
			var over = XG(this.wan1.inetp+"/ppp4/over");
			if (over === "eth")
			{	
				COMM_SetSelectValue(OBJ("wan_ip_mode"), "pppoe");
			}
		}
		else if (wan1addrtype === "ppp10")
		{
			var over = XG(this.wan1.inetp+"/ppp4/over");
			if (over === "eth")
			{
				COMM_SetSelectValue(OBJ("wan_ip_mode"), "pppoe");
			}
		}
		/* init ip setting */
		if (!this.InitIpv4Value()) return false;
		if (!this.InitPpp4Value()) return false;

		if(wan1addrtype === "ppp10")
		{
			var over = XG(this.wan1.inetp+"/ppp4/over");
			switch (over)
			{
				case "eth":
					if (XG(this.wan1.inetp+"/ppp4/static")==="1")	OBJ("pppoe_static").checked = true;
					else						OBJ("pppoe_dynamic").checked = true;
					OBJ("pppoe_ipaddr").value		= XG(this.wan1.inetp+"/ppp4/ipaddr");
					OBJ("pppoe_username").value		= XG(this.wan1.inetp+"/ppp6/username");
					OBJ("pppoe_password").value		= XG(this.wan1.inetp+"/ppp6/password");
					OBJ("confirm_pppoe_password").value	= XG(this.wan1.inetp+"/ppp6/password");
					OBJ("pppoe_service_name").value	= XG(this.wan1.inetp+"/ppp6/pppoe/servicename");

					OBJ("pppoe_max_idle_time").value = XG(this.wan1.inetp+"/ppp6/dialup/idletimeout");
					if (XG(this.wan1.inetp+"/ppp4/dns/count") > 0)	OBJ("dns_manual").checked = true;
					else OBJ("dns_isp").checked = true;
					OBJ("pppoe_dns1").value = XG(this.wan1.inetp+"/ppp4/dns/entry:1");
					if (XG(this.wan1.inetp+"/ppp4/dns/count")>=2) OBJ("pppoe_dns2").value = XG(this.wan1.inetp+"/ppp4/dns/entry:2");
					break;
			}
		}
		
		this.OnChangeWanIpMode();//20121106 jack move here
		//==20121220 jack add for PPPoE connet button==//
		this.PPP_Connectstatus();
		//==20121220 jack add for PPPoE connet button==//
		/* If Open DNS function is enabled, the DNS server would be fixed. */
		if(XG(this.wan1.infp+"/open_dns/type")!=="") this.DisableDNS();
		
		
		//sam_pan add
		this.dhcpc4 = PXML.FindModule("DHCPC4.WAN");
		if(XG(this.dhcpc4+"/dhcpc4/unicast")=="yes")
		{
			OBJ("dhcpc_unicast").checked = true;	
		}		
		return true;
	},
	PreSubmit: function()
	{
		/* disable all modules */
		PXML.IgnoreModule("DEVICE.LAYOUT");
		PXML.IgnoreModule("PHYINF.WAN-1");
		PXML.IgnoreModule("WAN");
		PXML.IgnoreModule("DHCPC4.WAN");
		
		PXML.IgnoreModule("INET.WAN-1");
		PXML.IgnoreModule("RUNTIME.INF.WAN-1");
		PXML.IgnoreModule("RUNTIME.PHYINF");
		
		/* clear WAN-2 & clone mac */
		XS(this.wan1.infp+"/lowerlayer","");
		XS(this.wan1.infp+"/upperlayer","");
		XS(this.wan1.inetp+"/ipv4/ipv4in6/mode","");
		XS(this.wan1.infp+"/infprevious", "");

		/*If previous type is ppp10,then change ipv6 type to LL*/
		var wan1addrtype = XG(this.wan1.inetp+"/addrtype");
		var over = XG(this.wan1.inetp+"/ppp4/over");
		if(wan1addrtype=="ppp10" && OBJ("wan_ip_mode").value!="pppoe")
		{
			BODY.ShowAlert("<?echo "IPv6 PPPoE is share with IPv4 PPPoE. Please change IPv6 WAN protocol to PPPoE at first.";?>");
			return null;
		}

		var mtu_obj = "ipv4_mtu";
		var mac_obj = "ipv4_macaddr";
		switch(OBJ("wan_ip_mode").value)
		{
		case "static":
			if (!this.PreStatic()) return null;
			break;
		case "dhcp":
			if (!this.PreDhcp()) return null;
			break;
		case "pppoe":
			if (!this.PrePppoe()) return null;
			mtu_obj = "ppp4_mtu";
			mac_obj = "ppp4_macaddr";
			break;
		}
		if (!TEMP_IsDigit(OBJ(mtu_obj).value))
		{
			BODY.ShowAlert("<?echo I18N("j","This MTU value is invalid.");?>");
			return null;
		}

		/* If mac is changed, restart PHYINF.WAN-1 and WAN, else restart WAN. */
		if (COMM_Equal(OBJ(mac_obj).getAttribute("modified"), true))
		{
			var p = PXML.FindModule("PHYINF.WAN-1");
			var b = GPBT(p, "phyinf", "uid", XG(p+"/inf/phyinf"), false);
			XS(b+"/macaddr", OBJ(mac_obj).value);
			PXML.ActiveModule("PHYINF.WAN-1");
			PXML.CheckModule("WAN", null, "ignore", null);
		}
		else
		{
			PXML.CheckModule("WAN", null, "ignore", null);
		}
		
		PXML.CheckModule("INET.INF", null, null, "ignore");
		PXML.ActiveModule("DHCPC4.WAN");
		/*If MAC clone is used, the device would reboot.*/
		if(COMM_Equal(OBJ("ipv4_macaddr").getAttribute("modified"), false) && COMM_Equal(OBJ("ppp4_macaddr").getAttribute("modified"), false)) PXML.IgnoreModule("REBOOT");
		
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	bootuptime: <?

		$bt=query("/runtime/device/bootuptime");
		if ($bt=="")	$bt=30;
		else			$bt=$bt+10;
		echo $bt;

	?>,
	defaultCFGXML: null,
	device_host: null,
	wan1:	{infp: null, inetp:null, phyinfp:null},
	InitIpv4Value: function()
	{
		/* static ip */
		if(XG(this.wan1.inetp+"/ipv4/ipaddr")=="")
			OBJ("st_ipaddr").value	= "0.0.0.0";
		else
			OBJ("st_ipaddr").value	= XG(this.wan1.inetp+"/ipv4/ipaddr");
		
		OBJ("st_mask").value	= COMM_IPv4INT2MASK(XG(this.wan1.inetp+"/ipv4/mask"));
		if(XG(this.wan1.inetp+"/ipv4/gateway")=="")
			OBJ("st_gw").value	= "0.0.0.0";
		else
			OBJ("st_gw").value		= XG(this.wan1.inetp+"/ipv4/gateway");
		
		/* dns server */
		var cnt = XG(this.wan1.inetp+"/ipv4/dns/count");
		OBJ("ipv4_dns1").value	= cnt > 0 ? XG(this.wan1.inetp+"/ipv4/dns/entry:1") : "";
		OBJ("ipv4_dns2").value	= cnt > 1 ? XG(this.wan1.inetp+"/ipv4/dns/entry:2") : "";
		OBJ("ipv4_mtu").value			= XG(this.wan1.inetp+"/ipv4/mtu");
		/* mac addr */
		//==20121206 jack put device mac on clone mac default==//
		if(XG(this.wan1.phyinfp+"/macaddr")=="")
			OBJ("ipv4_macaddr").value = "<?echo query("/runtime/devdata/wlanmac");?>";
		else
			OBJ("ipv4_macaddr").value = XG(this.wan1.phyinfp+"/macaddr");
		//==20121206 jack put device mac on clone mac default==//
		return true;
	},
	InitPpp4Value: function()
	{

		/* set/clear to default */
		/* pppoe */
		OBJ("pppoe_dynamic").checked		= true;
		OBJ("pppoe_ipaddr").value			= "";
		OBJ("pppoe_username").value			= "";
		OBJ("pppoe_password").value			= "";
		OBJ("confirm_pppoe_password").value = "";
		OBJ("pppoe_service_name").value		= "";
		OBJ("pppoe_alwayson").checked		= true;//20121108 jack cghange default value
		OBJ("pppoe_max_idle_time").value	= 5;
		OBJ("dns_isp").checked				= true;
		OBJ("pppoe_dns1").value				= "";
		OBJ("pppoe_dns2").value				= "";

		/* common */
		OBJ("ppp4_mtu").value = XG(this.wan1.inetp+"/ppp4/mtu");
		//==20121206 jack put device mac on clone mac default==//
		if(XG(this.wan1.phyinfp+"/macaddr")=="")
			OBJ("ppp4_macaddr").value = "<?echo query("/runtime/devdata/wlanmac");?>";
		else
			OBJ("ppp4_macaddr").value = XG(this.wan1.phyinfp+"/macaddr");
		//==20121206 jack put device mac on clone mac default==//

		/* init */
		var over = XG(this.wan1.inetp+"/ppp4/over");
		switch (over)
		{
		case "eth":
			if (XG(this.wan1.inetp+"/ppp4/static")==="1")
				OBJ("pppoe_static").checked = true;
			else
				OBJ("pppoe_dynamic").checked = true;
			OBJ("pppoe_ipaddr").value		= XG(this.wan1.inetp+"/ppp4/ipaddr");
			OBJ("pppoe_username").value		= XG(this.wan1.inetp+"/ppp4/username");
			OBJ("pppoe_password").value		= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("confirm_pppoe_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("pppoe_service_name").value	= XG(this.wan1.inetp+"/ppp4/pppoe/servicename");
			var dialup = XG(this.wan1.inetp+"/ppp4/dialup/mode");
			if		(dialup === "auto")		OBJ("pppoe_alwayson").checked = true;
			else if	(dialup === "manual")	OBJ("pppoe_manual").checked = true;
			else							OBJ("pppoe_ondemand").checked = true;
			OBJ("pppoe_max_idle_time").value = XG(this.wan1.inetp+"/ppp4/dialup/idletimeout")==""?5:XG(this.wan1.inetp+"/ppp4/dialup/idletimeout");
			if (XG(this.wan1.inetp+"/ppp4/dns/count") > 0)	OBJ("dns_manual").checked = true;
			else OBJ("dns_isp").checked = true;
			OBJ("pppoe_dns1").value = XG(this.wan1.inetp+"/ppp4/dns/entry:1");
			if (XG(this.wan1.inetp+"/ppp4/dns/count")>=2) OBJ("pppoe_dns2").value = XG(this.wan1.inetp+"/ppp4/dns/entry:2");
			break;
			
		}
		return true;
	},
	/* for Pre-Submit */
	PreStatic: function()
	{
		var cnt;
		XS(this.wan1.inetp+"/addrtype",		"ipv4");
		XS(this.wan1.inetp+"/ipv4/static",	"1");
		XS(this.wan1.inetp+"/ipv4/ipaddr",	OBJ("st_ipaddr").value);
		XS(this.wan1.inetp+"/ipv4/mask",	COMM_IPv4MASK2INT(OBJ("st_mask").value));
		XS(this.wan1.inetp+"/ipv4/gateway",	OBJ("st_gw").value);
		XS(this.wan1.inetp+"/ipv4/mtu",		OBJ("ipv4_mtu").value);

		var st_ip = OBJ("st_ipaddr").value;
		if(!check_ip_validity(st_ip))
		{
			BODY.ShowAlert("<?echo I18N("j","This IP address is invalid.");?>");
			OBJ("st_ipaddr").focus();
			return null;
		}

		cnt = 0;
		if(OBJ("ipv4_dns1").value === "")
		{
			BODY.ShowAlert("<?echo I18N("j","This DNS address is invalid.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ipv4/dns/entry", OBJ("ipv4_dns1").value);
		cnt+=1;
		if (OBJ("ipv4_dns2").value !== "")
		{
			XS(this.wan1.inetp+"/ipv4/dns/entry:2", OBJ("ipv4_dns2").value);
			cnt+=1;
		}
		XS(this.wan1.inetp+"/ipv4/dns/count", cnt);
		return true;
	},
	PreDhcp: function()
	{
		var cnt;
		XS(this.wan1.inetp+"/addrtype",			"ipv4");
		XS(this.wan1.inetp+"/ipv4/static",		"0");			
		
		cnt = 0;
		if(OBJ("ipv4_dns1").value !== "")
		{
			cnt+=1;
			XS(this.wan1.inetp+"/ipv4/dns/entry:"+cnt, OBJ("ipv4_dns1").value);
		}
		if (OBJ("ipv4_dns2").value !== "")
		{
			cnt+=1;
			XS(this.wan1.inetp+"/ipv4/dns/entry:"+cnt, OBJ("ipv4_dns2").value);
		}
		XS(this.wan1.inetp+"/ipv4/dns/count", cnt);
		XS(this.wan1.inetp+"/ipv4/mtu", OBJ("ipv4_mtu").value);
		
		//sam_pan add		
		XS(this.dhcpc4+"/dhcpc4/unicast", OBJ("dhcpc_unicast").checked?"yes":"no");
		return true;
	},
	PrePppoe: function()
	{
		var temp_value="";
		var cnt;
		
		if(OBJ("pppoe_username").value == "") 
		{
			BODY.ShowAlert("<?echo I18N("j","Enter a username.");?>");			
			return null;
		}
			
		if (OBJ("pppoe_password").value !== OBJ("confirm_pppoe_password").value)
		{
			BODY.ShowAlert("<?echo I18N("j","The password is incorrect.");?>");
			return null;
		}
		var wan1addrtype = XG(this.wan1.inetp+"/addrtype");
		var over = XG(this.wan1.inetp+"/ppp4/over");
		if(wan1addrtype=="ppp10" && over=="eth")
		{
			XS(this.wan1.inetp+"/addrtype", "ppp10");
			XS(this.wan1.inetp+"/ppp6/username", OBJ("pppoe_username").value);
			XS(this.wan1.inetp+"/ppp6/password", OBJ("pppoe_password").value);
			XS(this.wan1.inetp+"/ppp6/pppoe/servicename", OBJ("pppoe_service_name").value);
			XS(this.wan1.inetp+"/ppp6/mtu", OBJ("ppp4_mtu").value);
			XS(this.wan1.inetp+"/ppp6/over", "eth");
		}
		else
			XS(this.wan1.inetp+"/addrtype", "ppp4");
		
		XS(this.wan1.inetp+"/ppp4/over", "eth");
		XS(this.wan1.inetp+"/ppp4/username", OBJ("pppoe_username").value);
		var mppe = 0;
		XS(this.wan1.inetp+"/ppp4/mppe/enable", mppe);
		XS(this.wan1.inetp+"/ppp4/password", OBJ("pppoe_password").value);
		XS(this.wan1.inetp+"/ppp4/pppoe/servicename", OBJ("pppoe_service_name").value);
		if (OBJ("pppoe_dynamic").checked)
		{
			XS(this.wan1.inetp+"/ppp4/static", "0");
			XD(this.wan1.inetp+"/ppp4/ipaddr");
		}
		else
		{
			XS(this.wan1.inetp+"/ppp4/static", "1");
			XS(this.wan1.inetp+"/ppp4/ipaddr", OBJ("pppoe_ipaddr").value);
			var st_ip = OBJ("pppoe_ipaddr").value;
			if(!check_ip_validity(st_ip))
			{
				BODY.ShowAlert("<?echo I18N("j","This IP address is invalid.");?>");
				OBJ("pppoe_ipaddr").focus();
				return null;
			}
						
			if (OBJ("dns_manual").checked && OBJ("pppoe_dns1").value === "")
			{
				BODY.ShowAlert("<?echo I18N("j","This DNS address is invalid.");?>");
				return null;
			}
		}

		/* dns */
		cnt = 0;
		if (OBJ("dns_isp").checked)
		{
			XS(this.wan1.inetp+"/ppp4/dns/entry:1","");
			XS(this.wan1.inetp+"/ppp4/dns/entry:2","");
		}
		else
		{
			if (OBJ("pppoe_dns1").value !== "")
			{
				XS(this.wan1.inetp+"/ppp4/dns/entry", OBJ("pppoe_dns1").value);
				cnt+=1;
			}
			else XS(this.wan1.inetp+"/ppp4/dns/entry","");
			if (OBJ("pppoe_dns2").value !== "")
			{
				XS(this.wan1.inetp+"/ppp4/dns/entry:2", OBJ("pppoe_dns2").value);
				cnt+=1;
			}
		}
		XS(this.wan1.inetp+"/ppp4/dns/count", cnt);
		XS(this.wan1.inetp+"/ppp4/mtu", OBJ("ppp4_mtu").value);
		if (OBJ("pppoe_max_idle_time").value==="") OBJ("pppoe_max_idle_time").value = 0;
		if (!TEMP_IsDigit(OBJ("pppoe_max_idle_time").value))
		{
			BODY.ShowAlert("<?echo I18N("j","This value is invalid.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ppp4/dialup/idletimeout", OBJ("pppoe_max_idle_time").value);
		
		var dialup = "ondemand";
		if(OBJ("pppoe_alwayson").checked)
		{	
			dialup = "auto";
			//+++ Jerry Kao, Modified sync Reconnect Mode to IPv6, except "On demand" mode.
			XS(this.wan1.inetp+"/ppp6/dialup/mode", dialup);	
		}
		else if	(OBJ("pppoe_manual").checked)
		{
			dialup = "manual";
			XS(this.wan1.inetp+"/ppp6/dialup/mode", dialup);
		}
		XS(this.wan1.inetp+"/ppp4/dialup/mode", dialup);

		return true;
	},
	OnChangeWanIpMode: function()
	{
		OBJ("ipv4_setting").style.display		= "none";
		OBJ("ppp4_setting").style.display		= "none";

		OBJ("box_wan_static").style.display		= "none";
		OBJ("box_wan_dhcp").style.display		= "none";
		OBJ("box_wan_static_body").style.display= "none";
		OBJ("box_wan_dhcp_body").style.display	= "none";
		OBJ("box_wan_ipv4_common_body").style.display = "none";

		OBJ("box_wan_pppoe").style.display		= "none";
		//OBJ("show_pppoe_mppe").style.display	= "none";
		OBJ("box_wan_pppoe_body").style.display	= "none";
		OBJ("box_wan_ppp4_comm_body").style.display = "none";
		
		var over = XG(this.wan1.inetp+"/ppp4/over");
		switch(OBJ("wan_ip_mode").value)
		{
		case "static":
			OBJ("ipv4_setting").style.display				= "block";
			OBJ("box_wan_static").style.display				= "block"; 
			OBJ("box_wan_static_body").style.display		= "block";
			OBJ("box_wan_ipv4_common_body").style.display	= "block";
			break;
		case "dhcp":
			OBJ("ipv4_setting").style.display				= "block";
			OBJ("box_wan_dhcp").style.display				= "block";
			OBJ("box_wan_dhcp_body").style.display			= "block";
			OBJ("box_wan_ipv4_common_body").style.display	= "block";
			break;
		case "pppoe":
			OBJ("ppp4_setting").style.display				= "block";
			OBJ("box_wan_pppoe_body").style.display			= "block";
			OBJ("box_wan_pppoe").style.display				= "block";
			OBJ("box_wan_ppp4_comm_body").style.display		= "block";
			if (XG(this.wan1.inetp+"/ppp4/mtu")=="")		OBJ("ppp4_mtu").value = "1492";
			this.OnClickPppoeAddrType();
			this.OnClickPppoeReconnect();
			this.OnClickDnsMode();
			break;
		}
	},
	/* PPPoE */
	OnClickPppoeAddrType: function()
	{
		//+++ Jerry Kao, disable "On demand" if Reconnect Mode="Always on" in IPv6.
		if(XG(this.wan1.inetp+"/ppp6/dialup/mode") == "auto")
		{
			OBJ("pppoe_ondemand").disabled = true;
		}				

		OBJ("pppoe_ipaddr").disabled = OBJ("pppoe_dynamic").checked ? true: false;
	},
	OnClickPppoeReconnect: function()
	{
		if(OBJ("pppoe_alwayson").checked)
		{
			OBJ("pppoe_max_idle_time").disabled = true;
            OBJ("wan_ppp_connect").disabled = true;
            OBJ("wan_ppp_disconnect").disabled = false;

		}
		else if(OBJ("pppoe_ondemand").checked)
		{
			OBJ("pppoe_max_idle_time").disabled = false;
            OBJ("wan_ppp_connect").disabled = true;
            OBJ("wan_ppp_disconnect").disabled = true;
		}
		else
		{
			OBJ("pppoe_max_idle_time").disabled = true;
            OBJ("wan_ppp_connect").disabled = false;
            OBJ("wan_ppp_disconnect").disabled = false;
		}
	},
	OnClickDnsMode: function()
	{
		var dis = OBJ("dns_isp").checked;
		OBJ("pppoe_dns1").disabled = dis;
		OBJ("pppoe_dns2").disabled = dis;
	},
	OnClickMacButton: function(objname)
	{
		OBJ(objname).value="<?echo INET_ARP($_SERVER["REMOTE_ADDR"]);?>";
		if(OBJ(objname).value == "")
			alert("Can't find Your PC's MAC Address, please enter Your MAC manually.");
	},
	DisableDNS: function()
	{
		if(XG(this.wan1.infp+"/open_dns/type")==="advance") var open_dns_srv = "adv_dns_srv";
		else if(XG(this.wan1.infp+"/open_dns/type")==="family") var open_dns_srv = "family_dns_srv";
		else var open_dns_srv = "parent_dns_srv"; 
		var opendns_dns1 = XG(this.wan1.infp+"/open_dns/"+open_dns_srv+"/dns1");
		var opendns_dns2 = XG(this.wan1.infp+"/open_dns/"+open_dns_srv+"/dns2");		
		
		OBJ("ipv4_dns1").disabled = OBJ("ipv4_dns2").disabled = true;
		OBJ("pppoe_dns1").disabled = OBJ("pppoe_dns2").disabled = OBJ("dns_isp").disabled = OBJ("dns_manual").disabled = true;
		OBJ("ipv4_dns1").value = OBJ("pppoe_dns1").value = OBJ("pptp_dns1").value = opendns_dns1; 
		OBJ("ipv4_dns2").value = OBJ("pppoe_dns2").value = OBJ("pptp_dns2").value = opendns_dns2;	
	},
	//==20121219 jack add for PPPoE connect button==//
	PPP_Connect: function()
	{
	    var wan	= PXML.FindModule("INET.WAN-1");
		var combo = XG  (wan+"/inf/lowerlayer");
		if (combo !="") 
			WAN1COMBODIALUP();
		else
			WAN1PPPDIALUP();
	},
	PPP_Disconnect: function()
	{
	    var wan	= PXML.FindModule("INET.WAN-1");
	    var combo = XG  (wan+"/inf/lowerlayer");
	    if (combo !="")
			WAN1COMBOHANGUP();
	    else
			WAN1PPPHANGUP();
	},
	PPP_Connectstatus: function()
	{
		var wancable_status=0;
		var wan_network_status=0;
		var wan	= PXML.FindModule("INET.WAN-1");
		var rwan = PXML.FindModule("RUNTIME.INF.WAN-1");
		var rphy = PXML.FindModule("RUNTIME.PHYINF");
		var waninetuid = XG  (wan+"/inf/inet");
		var wanphyuid = XG  (wan+"/inf/phyinf");
		
		
		this.rwaninetp = GPBT(rwan+"/runtime/inf", "inet", "uid", waninetuid, false);
		this.rwanphyp = GPBT(rphy+"/runtime", "phyinf", "uid", wanphyuid, false);
		
		if((XG  (this.rwanphyp+"/linkstatus")!="0")&&(XG  (this.rwanphyp+"/linkstatus")!=""))
		{
			wancable_status=1;
		}
		
		var connStat = XG(rwan+"/runtime/inf/pppd/status");
		if ((XG  (this.rwaninetp+"/ppp4/valid")== "1")&& (wancable_status==1))
		{
			wan_network_status=1;
		}
		
		switch (connStat)
		{
			case "connected":
			if (wan_network_status == 1)
			{
				OBJ("wan_ppp_connect").disabled = true;
				OBJ("wan_ppp_disconnect").disabled = false;
			}
			else
			{
				OBJ("wan_ppp_connect").disabled = false;
				OBJ("wan_ppp_disconnect").disabled = true;
			}
			break;
			case "":
			case "disconnected":
			{
				OBJ("wan_ppp_connect").disabled = false;
				OBJ("wan_ppp_disconnect").disabled = true;
				wan_network_status=0;
			}
			break;
			case "on demand":
				OBJ("wan_ppp_connect").disabled = false;
				OBJ("wan_ppp_disconnect").disabled = true;
				wan_network_status=0;
			break;
			default:
				OBJ("wan_ppp_connect").disabled = false;
				OBJ("wan_ppp_disconnect").disabled = false;
				break;
		}
		//==20121220 jack add for PPPoE button==//
		//==alwayson can't connect && ondemand(auto) can't connect and disconnect==//
		if(OBJ("pppoe_alwayson").checked)
		{
            OBJ("wan_ppp_connect").disabled = true;
		}
		else if(OBJ("pppoe_ondemand").checked)
		{
            OBJ("wan_ppp_connect").disabled = true;
		}
		
	},
	//==20121219 jack add for PPPoE connect button==//
}


function IdleTime(value)
{
	if (value=="")
		return "0";
	else
		return parseInt(value, 10);
}

function check_ip_validity(ipstr)
{
	var vals = ipstr.split(".");
	if (vals.length!=4) 
		return false;
	
	for (var i=0; i<4; i++)
	{
		if (!TEMP_IsDigit(vals[i]) || vals[i]>255)
			return false;
	}
	return true;
}
function AddItemFromSelect(objSelect,objItemText,objectItemValue)
{
	//judge if exist
	for(var i=0;i<objSelect.length;i++)
	{
		if(objSelect[i].value==objectItemValue) return;
	}
	var varItem = document.createElement("option");
	varItem.text = objItemText;
	varItem.value = objectItemValue;
	try {objSelect.add(varItem, null);}
	catch(e){objSelect.add(varItem);}
	return;
}

</script>
