<?include "/htdocs/phplib/phyinf.php";?>
<script type="text/javascript">
function Page() {}
Page.prototype =
{	
	services: "WIFI.PHYINF,PHYINF.WIFI",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			BODY.OnReload();
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
		PXML.doc = xml;		
		if(!this.Initial("BAND24G-1.2","WIFI.PHYINF")) return false; 

		return true;
	},
	PreSubmit: function()
	{		
		if(!this.ValidityCheck("BAND24G-1.2")) return null; 				
		if(!this.WPSCHK("BAND24G-1.2")) return null; 
		if(!this.SaveXML("BAND24G-1.2")) return null; 			
		PXML.CheckModule("WIFI.PHYINF", null, null, "ignore");
		
		return PXML.doc;
	},			
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
    bootuptime: <?
	$bt=query("/runtime/device/bootuptime");
	if ($bt=="")    $bt=30;
	else            $bt=$bt+10;
	echo $bt;
	?>,
	wifip: null,
	phyinf: null,
	sec_type: null,
	sec_type_Aband: null,
	wps: true,

	str_Aband: null,
	del_idx: null,

	//feature_nosch: null,
	Initial: function(wlan_uid,wifi_module)
	{
		this.wifi_module 			= PXML.FindModule(wifi_module);
		if (!this.wifi_module)
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		this.phyinf = GPBT(this.wifi_module, "phyinf", "uid",wlan_uid, false);
		if(!this.phyinf)
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		var wifi_profile 	= XG(this.phyinf+"/wifi");
		this.wifip 			= GPBT(this.wifi_module+"/wifi", "entry", "uid", wifi_profile, false);
		OBJ("mssid_en").checked 			= COMM_ToBOOL(XG(this.phyinf+"/active"));
		OBJ("ssid").value 					= XG(this.wifip+"/ssid");
		OBJ("mssid_hidden").checked 		= COMM_ToBOOL(XG(this.wifip+"/ssidhidden"));
		OBJ("en_wmm").checked 				= COMM_ToBOOL(XG(this.phyinf+"/media/wmm/enable"));

	
		///////////////// initial WEP /////////////////
		var auth = XG(this.wifip+"/authtype");
		var len = (XG(this.wifip+"/nwkey/wep/size")=="")? "64" : XG(this.wifip+"/nwkey/wep/size");
		var defkey = (XG(this.wifip+"/nwkey/wep/defkey")=="")? "1" : XG(this.wifip+"/nwkey/wep/defkey");
		this.wps = COMM_ToBOOL(XG(this.wifip+"/wps/enable"));
		var wepauth = (auth=="SHARED") ? "SHARED" : "WEPAUTO";
		
		COMM_SetSelectValue(OBJ("auth_type"),	wepauth);
		COMM_SetSelectValue(OBJ("wep_key_len"),	len);
		COMM_SetSelectValue(OBJ("wep_def_key"),	defkey);
		for (var i=1; i<5; i++)
			OBJ("wep_"+len+"_"+i).value = XG(this.wifip+"/nwkey/wep/key:"+i);
		///////////////// initial WPA /////////////////
		var cipher = XG(this.wifip+"/encrtype");
		var sec_type = null;
		switch (auth)
		{
			case "WPA":
			case "WPA2":
			case "WPA+2":
			case "WPAPSK":
				sec_type = "wpa_personal";
				wpa_mode = "WPA";
				break;
			case "WPA2PSK":
				sec_type = "wpa_personal";
				wpa_mode = "WPA2";				
				break;
			case "WPA+2PSK":
				sec_type = "wpa_personal";
				wpa_mode = "WPA+2";
				break;
			default:
				sec_type = "";
				wpa_mode = "WPA+2";
		}

		if (cipher=="WEP")
			sec_type = "wep";
		COMM_SetSelectValue(OBJ("wpa_mode"), wpa_mode);
		
		this.sec_type 		= sec_type;

		OBJ("wpa_psk_key").value		= XG(this.wifip+"/nwkey/psk/key");

		OBJ("wpa_grp_key_intrv").value 	= (XG(this.wifip+"/nwkey/wpa/groupintv")=="")? "3600" : XG(this.wifip+"/nwkey/wpa/groupintv");
		
		this.wifi_module1 			= PXML.FindModule("WIFI.PHYINF");
		this.phyinf1 = GPBT(this.wifi_module1, "phyinf", "uid","BAND24G-1.1", false);
		var wlan_mode =  XG(this.phyinf1+"/media/wlmode");

		DrawSecurityList(wlan_mode);
		COMM_SetSelectValue(OBJ("security_type"), this.sec_type);
		COMM_SetSelectValue(OBJ("cipher_type"), cipher);	
		this.OnChangeSecurityType();//==20121122 jack add for show wep==//
		this.OnClickEnMSSID();

		return true;
	},
	SetWps: function(string)
	{
		var phyinf 		= GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		var wifip 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", XG(phyinf+"/wifi"), false);

		if(string=="enable")
		{
			XS(wifip+"/wps/enable", "1");
		}
		else
		{
			XS(wifip+"/wps/enable", "0");
		}
	},
	WPSCHK: function(wlan_uid)
	{
		var wifi_module = this.wifi_module;
		var phyinf 		= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var wifip 		= GPBT(wifi_module+"/wifi", "entry", "uid", XG(phyinf+"/wifi"), false);			

		if (COMM_EqBOOL(OBJ("ssid").getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("security_type").getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("cipher_type").getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("wpa_psk_key").getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("wep_def_key").getAttribute("modified"),true))
		{
			XS(wifip+"/wps/configured", "1");
		}
		
		//check authtype, if radius server is used, then wps must be disabled.
		var wps_enable = COMM_ToBOOL(XG(wifip+"/wps/enable"));
		
		if(wps_enable)
		{
			if(OBJ("security_type").value=="wep")
			{
				if(confirm('<?echo I18N("j", "WPS must be disabled in order to use WEP security. Proceed? ");?>'))
					this.SetWps("disable");
				else 
					return false;
			}
			
			if(OBJ("security_type").value=="wpa_personal")
			{
				if(OBJ("cipher_type").value == "TKIP")
				{
					if(confirm('<?echo I18N("j", "WPS must be disabled in order to use TKIP. Proceed? ");?>'))
						this.SetWps("disable");
					else
						return false;
				}
				if(OBJ("wpa_mode").value == "WPA")
				{
					if(confirm('<?echo I18N("j", "WPS must be disabled in order to use WPA. Proceed? ");?>'))
						this.SetWps("disable");
					else
						return false;
				}
			}
		
			if(OBJ("mssid_hidden").checked)
			{
				if(confirm('<?echo I18N("j", "WPS must be disabled in order to use hidden SSID (invisible). Proceed? ");?>'))
					this.SetWps("disable");
				else 
					return false;
			}
		}
		
		//for pass WPS 2.0 test, we add warning when security is disabled. 
		//var wifi_enabled = OBJ("en_wifi").checked;
		if(OBJ("security_type").value=="")
		{
				alert('<?echo I18N("j", "Selecting \"None\" in security mode will make your 2.4Ghz wireless connection vulnerable to access by third parties. Proceed?");?>');
				//jack remove comfirm for sony
		}
		return true;
	},

	SaveXML: function(wlan_uid)
	{
		var wifi_module 	= this.wifi_module;
		var phyinf 			= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var wifi_profile 	= XG(phyinf+"/wifi");
		var wifip 			= GPBT(wifi_module+"/wifi", "entry", "uid", wifi_profile, false);

		XS(phyinf+"/active",			SetBNode(OBJ("mssid_en").checked));
		XS(wifip+"/ssid",		OBJ("ssid").value);
		XS(wifip+"/ssidhidden",			SetBNode(OBJ("mssid_hidden").checked));
		XS(phyinf+"/media/wmm/enable",	SetBNode(OBJ("en_wmm").checked));
		
		if (OBJ("security_type").value=="wep")
		{
			if (OBJ("auth_type").value=="SHARED")
				XS(wifip+"/authtype", "SHARED");
			else
				XS(wifip+"/authtype", "WEPAUTO");
			XS(wifip+"/encrtype",			"WEP");
			XS(wifip+"/nwkey/wep/size",	OBJ("wep_key_len").value);
			XS(wifip+"/nwkey/wep/ascii",	"");
			XS(wifip+"/nwkey/wep/defkey",	OBJ("wep_def_key").value);
			for (var i=1, len=OBJ("wep_key_len").value; i<5; i++)
			{
				if (i==OBJ("wep_def_key").value)
					XS(wifip+"/nwkey/wep/key:"+i, OBJ("wep_"+len+"_"+i).value);
				else
					XS(wifip+"/nwkey/wep/key:"+i, "");
			}
		}
		else if (OBJ("security_type").value=="wpa_personal")
		{
			XS(wifip+"/authtype",				OBJ("wpa_mode").value+"PSK");
			XS(wifip+"/encrtype", 				OBJ("cipher_type").value);
			
			XS(wifip+"/nwkey/psk/passphrase",	"");
			XS(wifip+"/nwkey/psk/key",			OBJ("wpa_psk_key").value);
			XS(wifip+"/nwkey/wpa/groupintv",	OBJ("wpa_grp_key_intrv").value);
		}
		else
		{
			XS(wifip+"/authtype", "OPEN");
			XS(wifip+"/encrtype", "NONE");
		}

		return true;
	},
	OnChangeSecurityType: function()
	{
	
		//==20121016 jack add for save button==
		if(OBJ("security_type").value == "")
			OBJ("bsc_wlan_saveb").style.display = "block";
		else
			OBJ("bsc_wlan_saveb").style.display = "none";
		//==20121016 jack add for save button==
		
		switch (OBJ("security_type").value)
		{
			case "":
				OBJ("wep").style.display = "none";
				OBJ("box_wpa").style.display = "none";
				//OBJ("box_wpa_personal").style.display = "none";
				break;
			case "wep":
				OBJ("wep").style.display = "block";
				OBJ("box_wpa").style.display = "none";
				//OBJ("box_wpa_personal").style.display = "none";
				break;
			case "wpa_personal":
				OBJ("wep").style.display = "none";
				OBJ("box_wpa").style.display = "block";
				//OBJ("box_wpa_personal").style.display = "block";
				break;
		}
	},
	OnChangeWPAMode: function()
	{
		switch (OBJ("wpa_mode").value)
		{
			case "WPA":
				OBJ("cipher_type").value = "TKIP";
				break;
			case "WPA2":
				OBJ("cipher_type").value = "AES";
				break;	
			default :
				OBJ("cipher_type").value = "TKIP+AES";
		}
	},
	OnChangeWEPAuth: function()
	{
		if(OBJ("auth_type").value == "SHARED" && this.wps==true)
		{
			BODY.ShowAlert("<?echo I18N("j", '"Shared Key" cannot be selected when WPS is enabled.');?>");
			OBJ("auth_type").value = "WEPAUTO";
		}
	},
	OnChangeWEPKey: function()
	{
		var no = S2I(OBJ("wep_def_key").value) - 1;
		
		switch (OBJ("wep_key_len").value)
		{
			case "64":
				OBJ("wep_64").style.display = "block";
				OBJ("wep_128").style.display = "none";
				SetDisplayStyle(null, "wepkey_64", "none");
				document.getElementsByName("wepkey_64")[no].style.display = "inline";
				break;
			case "128":
				OBJ("wep_64").style.display = "none";
				OBJ("wep_128").style.display = "block";
				SetDisplayStyle(null, "wepkey_128", "none");
				document.getElementsByName("wepkey_128")[no].style.display = "inline";
		}
	},	
	//==20121122 jack add for disable wep in mssid==//
	DisableSecurityType: function(disable)
	{
		switch (OBJ("security_type").value)
		{
			case "":
				break;
			case "wep":
				OBJ("wep_key_len").disabled				= disable;
				OBJ("auth_type").disabled				= disable;
				OBJ("wep_64_1").disabled				= disable;
				OBJ("wep_128_1").disabled				= disable;
				break;
			case "wpa_personal":
				OBJ("wpa_mode").disabled				= disable;
				OBJ("cipher_type").disabled				= disable;
				OBJ("wpa_grp_key_intrv").disabled		= disable;
				OBJ("wpa_psk_key").disabled				= disable;
				break;
		}
	},
	//==20121122 jack add for disable wep in mssid==//
	
    /*
    For ssid, WEP key, WPA key, we don't allow whitespace in front OR behind !!!
    */
	OnClickEnMSSID: function()
	{
		if (OBJ("mssid_en").checked)
		{
			OBJ("ssid").disabled	= false;
			OBJ("mssid_hidden").disabled	= false;
			OBJ("en_wmm").disabled= false;
			OBJ("security_type").disabled= false;
			this.DisableSecurityType(false);
		}
		else
		{
			OBJ("ssid").disabled	= true;
			OBJ("mssid_hidden").disabled	= true;
			OBJ("en_wmm").disabled= true;
			OBJ("security_type").disabled= true;
		
			var auth = XG(this.wifip+"/authtype");
			switch (auth)
			{
				case "WPA":
				case "WPA2":
				case "WPA+2":
				case "WPAPSK":
				case "WPA2PSK":
				case "WPA+2PSK":
					COMM_SetSelectValue(OBJ("security_type"),"wpa_personal");
					sec_type = "wpa_personal";
					break;
				case "SHARED":
				case "WEPAUTO":
					COMM_SetSelectValue(OBJ("security_type"),"wep");
					break;
				default:
					COMM_SetSelectValue(OBJ("security_type"),"");
			}
			this.DisableSecurityType(true);
		}
		this.OnChangeSecurityType();
		this.OnChangeWEPKey();
	},
	
    ValidityCheck: function(wlan_uid)
	{ 
		var wifi_module 	= this.wifi_module;
		var phyinf 			= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var wifi_profile 	= XG(phyinf+"/wifi");
		var wifip 			= GPBT(wifi_module+"/wifi", "entry", "uid", wifi_profile, false);
		
		var obj_ssid 	= OBJ("ssid").value;
		var obj_wpa_key = OBJ("wpa_psk_key").value;

		var wep_key		= OBJ("wep_def_key").value;
		var wep_key_len	= OBJ("wep_key_len").value;			
		var obj_wep_key = OBJ("wep_"+wep_key_len+"_"+wep_key).value;		
		
		if(obj_ssid.charAt(0)===" "|| obj_ssid.charAt(obj_ssid.length-1)===" ")
		{
			alert("<?echo I18N("j", "A space cannot be used for the first or last character of the SSID.");?>");
			return false;
		}
		
		if(OBJ("security_type").value==="wep") //wep_64_1_Aband
		{
			if (obj_wep_key.charAt(0) === " "|| obj_wep_key.charAt(obj_wep_key.length-1)===" ")
			{
				alert("<?echo I18N("j", "A space cannot be used for the first or last character of the \"WEP Key\".");?>");
				return false;
			}
		}
		else if(OBJ("security_type").value==="wpa_personal")
		{
			if (obj_wpa_key.charAt(0)===" " || obj_wpa_key.charAt(obj_wpa_key.length-1)===" ")
			{
				alert("<?echo I18N("j", "A space cannot be used for the first or last character of the pre-shared key.");?>");
				return false;
			}
		}
		return true;
	}
}

function SetBNode(value)
{
	if (COMM_ToBOOL(value))
		return "1";
	else
		return "0";
}

function SetDisplayStyle(tag, name, style)
{
	if (tag)	var obj = GetElementsByName_iefix(tag, name);
	else		var obj = document.getElementsByName(name);
	for (var i=0; i<obj.length; i++)
	{
		obj[i].style.display = style;
	}
}
function GetElementsByName_iefix(tag, name)
{
	var elem = document.getElementsByTagName(tag);
	var arr = new Array();
	for(i = 0,iarr = 0; i < elem.length; i++)
	{
		att = elem[i].getAttribute("name");
		if(att == name)
		{
			arr[iarr] = elem[i];
			iarr++;
		}
	}
	return arr;
}

function DrawSecurityList(wlan_mode)
{
	var security_list = null;
	var cipher_list = null;
	if (wlan_mode === "n")
	{
		security_list = ['wpa_personal', '<?echo I18N("j","WPA-Personal");?>'];
		cipher_list = ['AES'];
	}
	else
	{
		security_list = ['wep', '<?echo I18N("j","WEP");?>',
						 'wpa_personal', '<?echo I18N("j","WPA-Personal");?>'];
		cipher_list = ['TKIP+AES','TKIP','AES'];
	}
	//modify security_type
	var sec_length = OBJ("security_type").length;
	for(var idx=1; idx<sec_length; idx++)
	{
		OBJ("security_type").remove(1);
	}
	for(var idx=0; idx<security_list.length; idx++)
	{
		var item = document.createElement("option");
		item.value = security_list[idx++];
		item.text = security_list[idx];
		try		{ OBJ("security_type").add(item, null); }
		catch(e){ OBJ("security_type").add(item); }
	}
	// modify cipher_type
	var ci_length = OBJ("cipher_type").length;
	for(var idx=0; idx<ci_length; idx++)
	{
		OBJ("cipher_type").remove(0);
	}
	for(var idx=0; idx<cipher_list.length; idx++)
	{
		var item = document.createElement("option");
		item.value = cipher_list[idx];
		if (item.value=="TKIP+AES") item.text = "AES/TKIP";
		else						item.text = cipher_list[idx];
		try		{ OBJ("cipher_type").add(item, null); }
		catch(e){ OBJ("cipher_type").add(item); }
	}
}
</script>
