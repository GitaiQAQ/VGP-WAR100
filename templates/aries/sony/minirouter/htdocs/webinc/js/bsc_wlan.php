<?include "/htdocs/phplib/phyinf.php";?>
<?	
	$hostname=query("/device/hostname");
	$mac=PHYINF_getmacsetting("LAN-1");
	$mac4=cut($mac, 4, ":");
	$mac5=cut($mac, 5, ":");
	$hostnamemac=$hostname.$mac4.$mac5;
	
?>
<script type="text/javascript">
function Page() {}
Page.prototype =
{	
	services: "WIFI.PHYINF,PHYINF.WIFI,DEVICE.LAYOUT,RUNTIME.PHYINF",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
				BODY.ShowContent();
		var url = null;
		var hostname= "<?=$hostname?>";
		var hostnamemac= "<?=$hostnamemac?>";
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
		
		if(!this.Initial("BAND24G-1.1","WIFI.PHYINF")) return false;
		return true;
	},
	PreSubmit: function()
	{		
		if(!this.ValidityCheck("BAND24G-1.1")) return null;
		
		if(!this.SaveXML("BAND24G-1.1")) return null;
		if(!this.WPSCHK("BAND24G-1.1")) return null;
		PXML.CheckModule("WIFI.PHYINF", null, null, "ignore");
		PXML.CheckModule("DEVICE.LAYOUT", null, null, "ignore");
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
	bandWidth: null,
	shortGuard: null,
	wps: true,
	radius_adv_flag: 0,
	wifi_module1: null,
	phyinf1: null,
	
	
	str_Aband: null,
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
		var freq 			= XG(this.phyinf+"/media/freq");
		this.wifip 			= GPBT(this.wifi_module+"/wifi", "entry", "uid", wifi_profile, false);
		var opmode			= XG(this.wifip+"/opmode");

		if(freq == "5") 	str_Aband = "_Aband";
		else				str_Aband = "";
		
		COMM_SetSelectValue(OBJ("wlan_mode"+str_Aband), XG(this.phyinf+"/media/wlmode"));
		
		OBJ("ssid"+str_Aband).value 				= XG(this.wifip+"/ssid");
		OBJ("auto_ch"+str_Aband).checked 			= (XG(this.phyinf+"/media/channel")=="0")? true : false;
		
		if (!OBJ("auto_ch"+str_Aband).checked)
			COMM_SetSelectValue(OBJ("channel"+str_Aband), XG(this.phyinf+"/media/channel"));
			
		OBJ("en_wmm"+str_Aband).checked = COMM_ToBOOL(XG(this.phyinf+"/media/wmm/enable"));
						
		OBJ("coexist_enable").checked 			= COMM_ToBOOL(XG(this.phyinf+"/media/dot11n/bw2040coexist"));			
		OBJ("ssid_hidden").checked 			= COMM_ToBOOL(XG(this.wifip+"/ssidhidden"));

		this.OnChangeWLMode(str_Aband);	//move from last sequence, bc. need to create security list

		///////////////// initial WEP /////////////////
		var auth = XG(this.wifip+"/authtype");
		var len = (XG(this.wifip+"/nwkey/wep/size")=="")? "64" : XG(this.wifip+"/nwkey/wep/size");
		var defkey = (XG(this.wifip+"/nwkey/wep/defkey")=="")? "1" : XG(this.wifip+"/nwkey/wep/defkey");
		this.wps = COMM_ToBOOL(XG(this.wifip+"/wps/enable"));
		var wepauth = (auth=="SHARED") ? "SHARED" : "WEPAUTO";
		
		COMM_SetSelectValue(OBJ("auth_type"+str_Aband),	wepauth);
		COMM_SetSelectValue(OBJ("wep_key_len"+str_Aband),	len);
		COMM_SetSelectValue(OBJ("wep_def_key"+str_Aband),	defkey);
		for (var i=1; i<5; i++)
			OBJ("wep_"+len+"_"+i+str_Aband).value = XG(this.wifip+"/nwkey/wep/key:"+i);
		///////////////// initial WPA /////////////////
		var cipher = XG(this.wifip+"/encrtype");
		var type = null;
		var sec_type = null;

		switch (auth)
		{
			case "WPA":
			case "WPA2":
			case "WPA+2":
				sec_type = "wpa_personal";
				wpa_mode = auth;
				break;
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
					
		COMM_SetSelectValue(OBJ("security_type"+str_Aband), sec_type);
		COMM_SetSelectValue(OBJ("wpa_mode"+str_Aband), wpa_mode);
		COMM_SetSelectValue(OBJ("cipher_type"+str_Aband), cipher);
				
		if(str_Aband == "")	this.sec_type 		= sec_type;
		else 				this.sec_type_Aband = sec_type;

		OBJ("wpa_psk_key"+str_Aband).value		= XG(this.wifip+"/nwkey/psk/key");

		this.OnChangeSecurityType(str_Aband);
		this.OnChangeWEPKey(str_Aband);

		this.OnClickEnAutoChannel(str_Aband);
		this.OnChangeChannel(str_Aband);
		return true;
	},
	
	SetWps: function(string)
	{
		var phyinf 		= GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		var wifip 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", XG(phyinf+"/wifi"), false);
		
		
		if(string=="enable")
			XS(wifip+"/wps/enable", "1");
		else
			XS(wifip+"/wps/enable", "0");
	},

	WPSCHK: function(wlan_uid)
	{
		var wifi_module = this.wifi_module;
		var phyinf 		= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var freq 		= XG(phyinf+"/media/freq");
		var wifip 		= GPBT(wifi_module+"/wifi", "entry", "uid", XG(phyinf+"/wifi"), false);			
		
		if(freq == "5")	str_Aband = "_Aband";
		else			str_Aband = "";
			
		if (COMM_EqBOOL(OBJ("ssid"+str_Aband).getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("security_type"+str_Aband).getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("cipher_type"+str_Aband).getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("wpa_psk_key"+str_Aband).getAttribute("modified"),true) ||
		COMM_EqBOOL(OBJ("wep_def_key"+str_Aband).getAttribute("modified"),true))
		{
			XS(wifip+"/wps/configured", "1");
		}
				
		var wps_enable = COMM_ToBOOL(XG(wifip+"/wps/enable"));
		
		if(wps_enable)
		{

			if(OBJ("security_type"+str_Aband).value=="wep")
			{
				if(confirm('<?echo I18N("j", "WPS must be disabled in order to use WEP security. Proceed? ");?>'))
					this.SetWps("disable");
				else 
					return false;
			}
			
			if(OBJ("security_type"+str_Aband).value=="wpa_personal")
			{
				if(OBJ("cipher_type"+str_Aband).value == "TKIP")
				{
					if(confirm('<?echo I18N("j", "WPS must be disabled in order to use TKIP. Proceed? ");?>'))
						this.SetWps("disable");
					else
						return false;
				}
				if(OBJ("wpa_mode"+str_Aband).value == "WPA")
				{
					if(confirm('<?echo I18N("j", "WPS must be disabled in order to use WPA. Proceed? ");?>'))
						this.SetWps("disable");
					else
						return false;
				}
			}
		
			if(OBJ("ssid_hidden").checked)
			{
				if(confirm('<?echo I18N("j", "WPS must be disabled in order to use hidden SSID (invisible). Proceed? ");?>'))
					this.SetWps("disable");
				else 
					return false;
			}
		}
		
		//for pass WPS 2.0 test, we add warning when security is disabled. 
		//var wifi_enabled = OBJ("en_wifi"+str_Aband).checked;
		//if(wifi_enabled && OBJ("security_type"+str_Aband).value=="")
		//wifi always enable on sony templates
		if(OBJ("security_type"+str_Aband).value=="")
		{
			if(str_Aband=="")
			{
				alert('<?echo I18N("j", "Selecting \"None\" in security mode will make your 2.4Ghz wireless connection vulnerable to access by third parties. Proceed?");?>');
				//jack remove comfirm for sony
			}
		}
		
		return true;
	},
	
	SaveXML: function(wlan_uid)
	{
		var wifi_module 	= this.wifi_module;
		var phyinf 			= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var wifi_profile 	= XG(phyinf+"/wifi");
		var wifip 			= GPBT(wifi_module+"/wifi", "entry", "uid", wifi_profile, false);
		var freq 			= XG(phyinf+"/media/freq");
		var opmode          = XG(wifip+"/opmode");

		if(freq == "5")		str_Aband = "_Aband";
		else				str_Aband = "";
		
		XS(wifip+"/ssid",		OBJ("ssid"+str_Aband).value);
		
		if (OBJ("auto_ch"+str_Aband).checked)
			XS(phyinf+"/media/channel", "0");
		else		
			XS(phyinf+"/media/channel", OBJ("channel"+str_Aband).value);			
		
		if (OBJ("txrate"+str_Aband).value=="-1")
		{
			XS(phyinf+"/media/dot11n/mcs/auto", "1");
			XS(phyinf+"/media/dot11n/mcs/index", "");
		}
		else
		{
			XS(phyinf+"/media/dot11n/mcs/auto", "0");
			XS(phyinf+"/media/dot11n/mcs/index", OBJ("txrate"+str_Aband).value);
		}
		XS(phyinf+"/media/wlmode",		OBJ("wlan_mode"+str_Aband).value);
		
		//==20121225 jack add for mssid's bw should be the same as main ssid==//
		this.wifi_module1 = PXML.FindModule("WIFI.PHYINF");
		this.phyinf1 = GPBT(this.wifi_module1, "phyinf", "uid","BAND24G-1.2", false);
		
		XS(this.phyinf1+"/media/wlmode",OBJ("wlan_mode"+str_Aband).value);
		
		//BODY.ShowAlert(XG(this.phyinf+"/media/wlmode"));
		//BODY.ShowAlert(XG(this.phyinf1+"/media/wlmode"));
		//==20121225 jack add for mssid's bw should be the same as main ssid==//
		if (/n/.test(OBJ("wlan_mode"+str_Aband).value))
		{
			XS(phyinf+"/media/dot11n/bandwidth",		OBJ("bw"+str_Aband).value);
			this.bandWidth = OBJ("bw"+str_Aband).value;
		}
		XS(phyinf+"/media/wmm/enable",	SetBNode(OBJ("en_wmm"+str_Aband).checked));
		XS(wifip+"/ssidhidden",			SetBNode(OBJ("ssid_hidden").checked));
		XS(phyinf+"/media/dot11n/bw2040coexist",	SetBNode(OBJ("coexist_enable").checked));
		if (OBJ("security_type"+str_Aband).value=="wep")
		{
			if (OBJ("auth_type"+str_Aband).value=="SHARED")
				XS(wifip+"/authtype", "SHARED");
			else
				XS(wifip+"/authtype", "WEPAUTO");
			XS(wifip+"/encrtype",			"WEP");
			XS(wifip+"/nwkey/wep/size", OBJ("wep_key_len").value);//==20121107 PAPA add for 64 & 128 bit key==
			XS(wifip+"/nwkey/wep/ascii",	"");
			XS(wifip+"/nwkey/wep/defkey",	OBJ("wep_def_key"+str_Aband).value);
			for (var i=1, len=OBJ("wep_key_len"+str_Aband).value; i<5; i++)
			{
				if (i==OBJ("wep_def_key"+str_Aband).value)
					XS(wifip+"/nwkey/wep/key:"+i, OBJ("wep_"+len+"_"+i+str_Aband).value);
				else
					XS(wifip+"/nwkey/wep/key:"+i, "");
			}
		}
		else if (OBJ("security_type"+str_Aband).value=="wpa_personal")
		{
			XS(wifip+"/authtype",				OBJ("wpa_mode"+str_Aband).value+"PSK");
			XS(wifip+"/encrtype", 				OBJ("cipher_type"+str_Aband).value);
			
			XS(wifip+"/nwkey/psk/passphrase",	"");
			XS(wifip+"/nwkey/psk/key",			OBJ("wpa_psk_key"+str_Aband).value);
		}
		else
		{
			XS(wifip+"/authtype", "OPEN");
			XS(wifip+"/encrtype", "NONE");
		}
		return true;
	},

	OnChangeChannel: function(str_Aband)
	{
		if (!OBJ("auto_ch"+str_Aband).checked)
		{
			if(OBJ("channel"+str_Aband).value=="140"|OBJ("channel"+str_Aband).value=="165" |  OBJ("channel"+str_Aband).value=="12"|OBJ("channel"+str_Aband).value=="13")
			{
				OBJ("bw"+str_Aband).value="20";
				OBJ("bw"+str_Aband).disabled = true;				
			}
			else
			{
				OBJ("bw"+str_Aband).disabled = false;
			}
		}

	},
	OnClickEnAutoChannel: function(str_Aband)
	{
		if (OBJ("auto_ch"+str_Aband).checked)
		{
			var rphy = PXML.FindModule("RUNTIME.PHYINF");
			var rwlan1p = 	GPBT(rphy+"/runtime", "phyinf", "uid", "BAND24G-1.1", false);
			var host_channel = XG  (rwlan1p+"/media/channel");
			COMM_SetSelectValue(OBJ("channel"+str_Aband), host_channel);
			OBJ("channel"+str_Aband).disabled = true;
		}
		else
			OBJ("channel"+str_Aband).disabled = false;
	},
	OnChangeSecurityType: function(str_Aband)
	{
	
		//==20121016 jack add for save button==
		if(OBJ("security_type"+str_Aband).value == "")
			OBJ("bsc_wlan_saveb").style.display = "block";
		else
			OBJ("bsc_wlan_saveb").style.display = "none";
		//==20121016 jack add for save button==
		
		switch (OBJ("security_type"+str_Aband).value)
		{
			case "":
				OBJ("wep"+str_Aband).style.display = "none";
				OBJ("box_wpa"+str_Aband).style.display = "none";
				break;
			case "wep":
				OBJ("wep"+str_Aband).style.display = "block";
				OBJ("box_wpa"+str_Aband).style.display = "none";
				break;
			case "wpa_personal":
				OBJ("wep"+str_Aband).style.display = "none";
				OBJ("box_wpa"+str_Aband).style.display = "block";
				break;
		}
	},
	OnChangeWPAMode: function(str_Aband)
	{
		switch (OBJ("wpa_mode"+str_Aband).value)
		{
			case "WPA":
				OBJ("cipher_type"+str_Aband).value = "TKIP";
				break;
			case "WPA2":
				OBJ("cipher_type"+str_Aband).value = "AES";
				break;	
			default :
				OBJ("cipher_type"+str_Aband).value = "TKIP+AES";
		}
	},
	OnChangeWEPAuth: function(str_Aband)
	{
		if(OBJ("auth_type"+str_Aband).value == "SHARED" && this.wps==true)
		{
			//BODY.ShowAlert("\"Shared Key\" cannot be selected when WPS is enabled.");
			BODY.ShowAlert("<?echo I18N("j", '"Shared Key" cannot be selected when WPS is enabled.');?>");
			OBJ("auth_type"+str_Aband).value = "WEPAUTO";
		}
	},
	OnChangeWEPKey: function(str_Aband)
	{
		var no = S2I(OBJ("wep_def_key"+str_Aband).value) - 1;
		
		switch (OBJ("wep_key_len"+str_Aband).value)
		{
			case "64":
				OBJ("wep_64"+str_Aband).style.display = "block";
				OBJ("wep_128"+str_Aband).style.display = "none";
				SetDisplayStyle(null, "wepkey_64"+str_Aband, "none");
				document.getElementsByName("wepkey_64"+str_Aband)[no].style.display = "inline";
				break;
			case "128":
				OBJ("wep_64"+str_Aband).style.display = "none";
				OBJ("wep_128"+str_Aband).style.display = "block";
				SetDisplayStyle(null, "wepkey_128"+str_Aband, "none");
				document.getElementsByName("wepkey_128"+str_Aband)[no].style.display = "inline";
		}
	},
	OnChangeWLMode: function(str_Aband)
	{	
		var phywlan = "";
		if(str_Aband==="")	phywlan = GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		//else				phywlan = GPBT(this.wifi_module, "phyinf", "uid","BAND5G-1.1", false);
		if (/n/.test(OBJ("wlan_mode"+str_Aband).value))
		{		
			this.bandWidth	= XG(phywlan+"/media/dot11n/bandwidth");
			COMM_SetSelectValue(OBJ("bw"+str_Aband), this.bandWidth);
			if(this.bandWidth == "20")
			{
    			OBJ("coexist_enable").disabled = true;
    		}
    		else
    		{
				OBJ("coexist_enable").disabled = false;
    		}							
			OBJ("bw"+str_Aband).disabled	= false;
			OBJ("en_wmm"+str_Aband).checked = true;
			OBJ("en_wmm"+str_Aband).disabled = true;
		}
		else
		{
			OBJ("bw"+str_Aband).disabled	= true;
			OBJ("coexist_enable").disabled = true;
			OBJ("en_wmm"+str_Aband).disabled = false;
		}
		this.shortGuard = XG(phywlan+"/media/dot11n/guardinterval");
		DrawTxRateList(OBJ("bw"+str_Aband).value, this.shortGuard, str_Aband);
		if (OBJ("wlan_mode"+str_Aband).value === "n")
		{
			var rate = XG(phywlan+"/media/dot11n/mcs/index");
			if (rate=="") rate = "-1";
			COMM_SetSelectValue(OBJ("txrate"+str_Aband), rate);
		}
	},
	
	OnChangeBW: function(str_Aband)
	{
		var phywlan = "";
		if(str_Aband==="")	phywlan = GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		//else				phywlan = GPBT(this.wifi_module, "phyinf", "uid","BAND5G-1.1", false);

		var bandWidth	= OBJ("bw"+str_Aband).value;
	
		if(bandWidth == "20")
		{
    		OBJ("coexist_enable").disabled = true;
    	}
    	else
    	{
			OBJ("coexist_enable").disabled = false;
    	}	
		this.shortGuard = XG(phywlan+"/media/dot11n/guardinterval");
		DrawTxRateList(OBJ("bw"+str_Aband).value, this.shortGuard, str_Aband);
	},
	
    /*
    For ssid, WEP key, WPA key, we don't allow whitespace in front OR behind !!!
    */
    ValidityCheck: function(wlan_uid)
	{ 
		var wifi_module 	= this.wifi_module;
		var phyinf 			= GPBT(wifi_module,"phyinf","uid",wlan_uid,false);
		var wifi_profile 	= XG(phyinf+"/wifi");
		var wifip 			= GPBT(wifi_module+"/wifi", "entry", "uid", wifi_profile, false);
		var freq 			= XG(phyinf+"/media/freq");

		if(freq == "5")		str_Aband = "_Aband";
		else				str_Aband = "";
		
		var obj_ssid 	= OBJ("ssid"+str_Aband).value;
		var obj_wpa_key = OBJ("wpa_psk_key"+str_Aband).value;

		var wep_key		= OBJ("wep_def_key"+str_Aband).value;
		var wep_key_len	= OBJ("wep_key_len"+str_Aband).value;			
		var obj_wep_key = OBJ("wep_"+wep_key_len+"_"+wep_key+str_Aband).value;		
		
		if(obj_ssid.charAt(0)===" "|| obj_ssid.charAt(obj_ssid.length-1)===" ")
		{
			alert("<?echo I18N("j", "A space cannot be used for the first or last character of the SSID.");?>");
			return false;
		}
		
		if(OBJ("security_type"+str_Aband).value==="wep") //wep_64_1_Aband
		{
			if (obj_wep_key.charAt(0) === " "|| obj_wep_key.charAt(obj_wep_key.length-1)===" ")
			{
				alert("<?echo I18N("j", "A space cannot be used for the first or last character of the \"WEP Key\".");?>");
				return false;
			}
		}
		else if(OBJ("security_type"+str_Aband).value==="wpa_personal")
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

function DrawTxRateList(bw, sgi, str_Aband)
{
	var listOptions = null;
	var cond = bw+":"+sgi;
	switch(cond)
	{
	case "20:800":
		listOptions = new Array("0 - 6.5","1 - 13.0","2 - 19.5","3 - 26.0","4 - 39.0","5 - 52.0","6 - 58.5","7 - 65.0"<?
						$p = XNODE_getpathbytarget("/runtime", "phyinf", "uid", "BAND24G-1.1", 0);
						$ms = query($p."/media/multistream");
						if ($ms != "1T1R")
							echo ',"8 - 13.0","9 - 26.0","10 - 39.0","11 - 52.0","12 - 78.0","13 - 104.0","14 - 117.0","15 - 130.0"';
						?>);
		break;
	case "20:400":
		listOptions = new Array("0 - 7.2","1 - 14.4","2 - 21.7","3 - 28.9","4 - 43.3","5 - 57.8","6 - 65.0","7 - 72.0"<?
						if ($ms != "1T1R")
							echo ',"8 - 14.444","9 - 28.889","10 - 43.333","11 - 57.778","12 - 86.667","13 - 115.556","14 - 130.000","15 - 144.444"';
						?>);
		break;
	case "20+40:800":
		listOptions = new Array("0 - 13.5","1 - 27.0","2 - 40.5","3 - 54.0","4 - 81.0","5 - 108.0","6 - 121.5","7 - 135.0"<?
						if ($ms != "1T1R")
							echo ',"8 - 27.0","9 - 54.0","10 - 81.0","11 - 108.0","12 - 162.0","13 - 216.0","14 - 243.0","15 - 270.0"';
						?>);
		break;
	case "20+40:400":
		listOptions = new Array("0 - 15.0","1 - 30.0","2 - 45.0","3 - 60.0","4 - 90.0","5 - 120.0","6 - 135.0","7 - 150.0"<?
						if ($ms != "1T1R")
							echo ',"8 - 30.0","9 - 60.0","10 - 90.0","11 - 120.0","12 - 180.0","13 - 240.0","14 - 270.0","15 - 300.0"';
						?>);
		break;
	}

	var tr_length = OBJ("txrate"+str_Aband).length;
	for(var idx=1; idx<tr_length; idx++)
	{
		OBJ("txrate"+str_Aband).remove(1);
	}
	if (OBJ("wlan_mode"+str_Aband).value === "n")
	{
		for(var idx=0; idx<listOptions.length; idx++)
		{
			var item = document.createElement("option");
			item.value = idx;
			item.text = listOptions[idx];
			try		{ OBJ("txrate"+str_Aband).add(item, null); }
			catch(e){ OBJ("txrate"+str_Aband).add(item); }
		}
	}
}

function DrawSecurityList(wlan_mode, str_Aband)
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
		cipher_list = ['TKIP+AES','AES','TKIP'];
	}
	//modify security_type
	var sec_length = OBJ("security_type"+str_Aband).length;
	for(var idx=1; idx<sec_length; idx++)
	{
		OBJ("security_type"+str_Aband).remove(1);
	}
	for(var idx=0; idx<security_list.length; idx++)
	{
		var item = document.createElement("option");
		item.value = security_list[idx++];
		item.text = security_list[idx];
		try		{ OBJ("security_type"+str_Aband).add(item, null); }
		catch(e){ OBJ("security_type"+str_Aband).add(item); }
	}
	// modify cipher_type
	var ci_length = OBJ("cipher_type"+str_Aband).length;
	for(var idx=0; idx<ci_length; idx++)
	{
		OBJ("cipher_type"+str_Aband).remove(0);
	}
	for(var idx=0; idx<cipher_list.length; idx++)
	{
		var item = document.createElement("option");
		item.value = cipher_list[idx];
		if (item.value=="TKIP+AES") item.text = "AES/TKIP";
		else						item.text = cipher_list[idx];
		try		{ OBJ("cipher_type"+str_Aband).add(item, null); }
		catch(e){ OBJ("cipher_type"+str_Aband).add(item); }
	}
}
</script>
