<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "MACCTRL,WIFI.PHYINF,PHYINF.WIFI,RUNTIME.WPS",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return false; },

	wifip: null,
	defpin: '<?echo query("/runtime/devdata/pin");?>',
	curpin: null,
	dual_band: COMM_ToBOOL('<?=$FEATURE_DUAL_BAND?>'),	
	wifi_module: null,

	InitValue: function(xml)
	{
		PXML.doc = xml;
		this.wifi_module 	= PXML.FindModule("WIFI.PHYINF");
		this.phyinf 		= GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		this.wifip 			= XG(this.phyinf+"/wifi");
		this.wifip 			= GPBT(this.wifi_module+"/wifi", "entry", "uid", this.wifip, false);
		this.wpsp			= PXML.FindModule("RUNTIME.WPS");

		if (!this.wifip || !this.wpsp)
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		
		this.wpsp += "/runtime/wps/setting";	
		var wps_enable 		= XG(this.wifip+"/wps/enable");
		//var wps_configured  = XG(this.wifip+"/wps/configured");
		var str_info = "";
		
		OBJ("en_wps").checked = COMM_ToBOOL(wps_enable);
		if (XG(this.wifip+"/wps/pin")=="")
			this.curpin = OBJ("pin").innerHTML = this.defpin;
		else
			this.curpin = OBJ("pin").innerHTML = XG(this.wifip+"/wps/pin");
		//==20130322 disable wps while mac filter is on==
		if (XG(this.wifip+"/acl/policy")=="ACCEPT" || XG(this.wifip+"/acl/policy")=="DROP")
			OBJ("en_wps").disabled	= true;
		else
			OBJ("en_wps").disabled	= false;
			
		if(this.dual_band)
		{
			this.phyinf2 	= GPBT(this.wifi_module, "phyinf", "uid","BAND5G-1.1", false);
			this.wifip2 	= XG(this.phyinf2+"/wifi");
			this.wifip2 	= GPBT(this.wifi_module+"/wifi", "entry", "uid", this.wifip2, false);
		}
			
		if (XG(this.wpsp+"/aplocked") != "1")
		{
			OBJ("lock_wifi_security").disabled	= true;
			OBJ("lock_wifi_security").checked	= false;
		}
		else
		{
			OBJ("lock_wifi_security").disabled	= false;
			OBJ("lock_wifi_security").checked	= true;
		}

		this.OnClickEnWPS();
				
		return true;
	},
	PreSubmit: function()
	{
		var lock_wps_security = OBJ("lock_wifi_security").checked ? "1":"0";
		
		XS(this.wifip+"/wps/enable", (OBJ("en_wps").checked)? "1":"0");
		
		if(this.dual_band)
		{
			XS(this.wifip2+"/wps/enable", (OBJ("en_wps").checked)? "1":"0");
		}
		XS(this.wpsp+"/aplocked", lock_wps_security);
		//check authtype, if we use radius server, then wps can't be enabled.
		//check authtype, if we use WEP security, then wps can't be enabled.
		if(OBJ("en_wps").checked)
		{
			if(!this.Is_SecuritySupportedByWps(this.wifip) || 
				(this.dual_band && !this.Is_SecuritySupportedByWps(this.wifip2)) )
		{
			OBJ("en_wps").checked		= false;
				BODY.ShowAlert("<?echo I18N("j", "WPS cannot be enabled when the following types of security are used:"). "\\n". 
					I18N("j","- WPA-Personal") . "\\n". 
					I18N("j","- WEP") . "\\n". 
							I18N("j","Please select a different type of security in order to enable WPS.");?>");
			return null;
		}
			
			if(this.Is_HiddenSsid(this.phyinf, this.wifip) || 
				(this.dual_band && this.Is_HiddenSsid(this.phyinf2, this.wifip2)) )
			{
				OBJ("en_wps").checked		= false;
				BODY.ShowAlert("<?echo I18N("j", "WPS cannot be enabled when hidden SSID (invisible) is enabled."). "\\n".
								I18N("j","Disable hidden SSID (invisible) in order to enable WPS.");?>");
				return null;
			}
			
			var wifi_verify	= "<? echo get('', '/runtime/devdata/wifiverify');?>";
			if(wifi_verify=="1" && this.Is_MacFilterEnabled())
			{
				OBJ("en_wps").checked		= false;
				BODY.ShowAlert("<?echo I18N("j", "WPS can't be enabled when Access Control is enabled."). "\\n".
								I18N("j","Disable MAC address filtering in order to enable WPS.");?>");
				return null;
			}
		}
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function()
	{
		if (OBJ("pin").innerHTML!=this.curpin)
		{
			OBJ("mainform").setAttribute("modified", "true");
			XS(this.wifip+"/wps/pin", OBJ("pin").innerHTML);
			if(this.dual_band)
				XS(this.wifip2+"/wps/pin", OBJ("pin").innerHTML);
		}
	},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	//OnCheckWPAEnterprise:function()
	Is_SecuritySupportedByWps:function(wifipath)
	{
		var auth = XG(wifipath+"/authtype");
		var cipher = XG(wifipath+"/encrtype");
		var issupported = true;
		
		//wpa-enterprise all not supported
		switch(auth)
		{
			case "WPA":
			case "WPA2":
			case "WPA+2":
			case "WPAEAP":
			case "WPA+2EAP":			
			case "WPA2EAP":
				issupported = false;
				break;
			default : 
				issupported = true;
				break;
		}
		
		//wep all not supported
		if (cipher=="WEP")
			issupported = false;
		
		//wpa-personal, "wpa only" or "tkip only" not supported
		if(auth=="WPAPSK" || cipher=="TKIP")
			issupported = false;
		return issupported;
	}, 
	
	
	Is_MacFilterEnabled:function()
	{
		this.macfp = PXML.FindModule("MACCTRL");
		if (!this.macfp) { return false; }
		this.macfp += "/acl/macctrl";
		var policy = "";
		
		if ((policy = XG(this.macfp+"/policy")) !== "")
		{	
			if(policy == "DISABLE")
				return false;
			else 
				return true;
		}
		
		return false;
	},
	
	
	Is_HiddenSsid:function(phyinfpath, wifipath)
	{
		if(XG(phyinfpath+"/active")=="1" && XG(wifipath+"/ssidhidden")=="1")
			return true;
		else 
			return false;
	}, 

	OnClickEnWPS: function()
	{
		var en_wlan = XG(this.phyinf+"/active");
		var en_wlan2 = XG(this.phyinf2+"/active");
		
		if(en_wlan == 0 && en_wlan2 == 0)
		{
			OBJ("en_wps").checked 		= false;
			OBJ("en_wps").disabled		= true;
		}
		if (OBJ("en_wps").checked)
		{
			if (XG(this.wifip+"/wps/configured")=="0")
				OBJ("reset_cfg").disabled = true;
			else
				OBJ("reset_cfg").disabled = false;
				
			OBJ("go_wps").disabled = false;
		}
		else
		{
			OBJ("reset_cfg").disabled	= true;
			OBJ("go_wps").disabled = true;
		}
	},
	OnClickResetCfg: function()
	{
		if (confirm("<?echo "Are you sure you want to reset the device to Unconfigured?"."\\n".
					"This will cause wireless settings to be lost.";?>"))
		{
			Service("RESETCFG.WIFI");
			PXML.CheckModule("WIFI.PHYINF", "ignore", "ignore", "ignore");
			
			OBJ("mainform").setAttribute("modified", "true");
			OBJ("lock_wifi_security").checked = false;
			BODY.OnSubmit();
		}
	}
}

function Service(svc)
{	
	var ajaxObj = GetAjaxObj("SERVICE");
	ajaxObj.createRequest();
	ajaxObj.onCallback = function (xml)
	{
		ajaxObj.release();
		if (xml.Get("/report/result")!="OK")
			BODY.ShowAlert("Internal ERROR!\nEVENT "+svc+": "+xml.Get("/report/message"));
	}
	ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxObj.sendRequest("service.cgi", "EVENT="+svc);
}

</script>
