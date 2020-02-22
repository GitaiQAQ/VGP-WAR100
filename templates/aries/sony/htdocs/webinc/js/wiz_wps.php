
<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "WIFI.PHYINF",
	OnLoad: function()
	{
		this.ShowCurrentStage();
	},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		switch (code)
		{
		case "OK":
			this.WPSInProgress();
			break;
		default:
			BODY.ShowAlert(result);
			break;
		}
		return true;
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;	
		if (!this.Initial("BAND24G-1.1", "WIFI.PHYINF")) return false;
		return true;
	},
	PreSubmit: function() { return null; },
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	m_prefix: "<?echo I18N("j","Adding wireless device...");?>",
	m_success: "<?echo I18N("j","Successfully connected to the device.").". ".I18N("j","Click the Cancel button to add another device. Click [Status] - [Wireless Connection] to check whether the device was added.");?>",
	m_timeout: "<?echo I18N("j","WPS was interrupted because connection to the device could not be confirmed.");?>",
	wifip: null,
	en_wps: false,
	start_count_down: false,
	wps_timer: null,
	phyinf:null,	
	wifi_phyinf:null,
	stages: new Array ("wiz_stage_2_auto","wiz_stage_2_msg"),
	currentStage: 0,	// 0 ~ this.stages.length
	
	Initial: function(wlan_phyinf, wifi_phyinf)
	{
		this.wifi_phyinf = PXML.FindModule(wifi_phyinf);
		
		if (!this.wifi_phyinf )
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		
		this.phyinf = GPBT(this.wifi_phyinf, "phyinf", "uid",wlan_phyinf, false);
		var wifi_profile = XG(this.phyinf+"/wifi");
		this.wifip = GPBT(this.wifi_phyinf+"/wifi", "entry", "uid", wifi_profile, false);
		
		PXML.IgnoreModule("WIFI.PHYINF");
		
		var freq = XG(this.phyinf+"/media/freq");
		if(freq == "5") 		str_Aband = "_Aband";
		else					str_Aband = "";
		
		this.en_wps = XG(this.wifip+"/wps/enable")=="1" ? true : false ; 
		if(!this.en_wps)
		{
			this.ShowWpsDisabled();
			return true;
		}
			
		
		switch (XG(this.wifip+"/authtype"))
		{
		case "SHARED":
			this.en_wps = false;
			DisableWPS();
			break;
		}
		return true;
	},
	ShowCurrentStage: function()
	{
		for (var i=0; i<this.stages.length; i++)
		{
			if (i==this.currentStage)
				OBJ(this.stages[i]).style.display = "block";
			else
				OBJ(this.stages[i]).style.display = "none";
		}
	},
	ShowWPSMessage: function(state)
	{
		switch (state)
		{
		case "WPS_NONE":
			OBJ("msg").innerHTML = this.m_prefix + "<?echo I18N("j","WPS was interrupted because connection to the device could not be confirmed.");?>";
			SetButtonDisabled("b_exit",	false);
			SetButtonDisplayNone("b_exit", "inline");
			SetButtonDisplayNone("b_send", "none");
			break;
		case "WPS_ERROR":
			OBJ("msg").innerHTML = this.m_prefix + "WPS_ERROR.";
			SetButtonDisabled("b_exit",	false);
			SetButtonDisplayNone("b_exit", "inline");
			SetButtonDisplayNone("b_send", "none");
			break;
		case "WPS_OVERLAP":
			OBJ("msg").innerHTML = this.m_prefix + "WPS_OVERLAP.";
			SetButtonDisabled("b_exit",	false);
			SetButtonDisplayNone("b_exit", "inline");
			SetButtonDisplayNone("b_send", "none");
			break;
		case "WPS_IN_PROGRESS":
			SetButtonDisabled("b_exit",	true);
			SetButtonDisabled("b_send",	true);
			SetButtonDisplayNone("b_send", "none");
			SetButtonDisplayNone("b_exit", "none");
			break;
		case "WPS_SUCCESS":
			OBJ("msg").innerHTML = this.m_prefix + "<?echo I18N("j","Successfully connected to the device.").". ".I18N("j","Click the Cancel button to add another device. Click [Status] - [Wireless Connection] to check whether the device was added.");?>";
			SetButtonDisabled("b_exit",	false);
			SetButtonDisabled("b_send",	true);
			SetButtonDisplayNone("b_exit", "inline");
			SetButtonDisplayNone("b_send", "none");
			break;
		}
		this.currentStage = 1;//ori is 3
		this.ShowCurrentStage();
		if (state=="WPS_IN_PROGRESS")	return;
		PAGE.start_count_down = false;
		if (this.cd_timer)	clearTimeout(this.cd_timer);
		if (this.wps_timer)	clearTimeout(this.wps_timer);
	},
	OnClickCancel: function()
	{
		if (this.currentStage==1)//ori is 3
		{
			self.location.href = "./wiz_wps.php";
			return;
		}
		if (!COMM_IsDirty(false)||confirm("<?echo I18N("j","Discard Changes?");?>"))
			self.location.href = "./adv_wps.php";
	},
	OnSubmit: function()
	{
		var ajaxObj = GetAjaxObj("WPS");
		var action = "PIN";
		var uid = "BAND24G-1.1";
		var value = OBJ("pincode").value;
		ajaxObj.createRequest();
		ajaxObj.onCallback = function (xml)
		{
			ajaxObj.release();
			PAGE.OnSubmitCallback(xml.Get("/wpsreport/result"), xml.Get("/wpsreport/reason"));
		}
		
		ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
		ajaxObj.sendRequest("wpsacts.php", "action="+action+"&uid="+uid+"&pin="+value);
		AUTH.UpdateTimeout();
	},
	WPSInProgress: function()
	{
		if (!this.start_count_down)
		{
			this.start_count_down = true;
			var str = "";
			str = "<?echo I18N("j","Please start WPS on the wireless device you want to add to enable connection to the router.");?><br />";
			str += '<?echo I18N("j","Remaining time in seconds");?>: <span id="ct">120</span><br /><br />';
			str += this.m_prefix + "<?echo I18N("j","Router WPS started.");?>.";
			OBJ("msg").innerHTML = str;
			this.ShowWPSMessage("WPS_IN_PROGRESS");
			setTimeout('PAGE.WPSCountDown()',1000);
		}

		var ajaxObj = GetAjaxObj("WPS");
		ajaxObj.createRequest();
		ajaxObj.onCallback = function (xml)
		{
			ajaxObj.release();
			PAGE.WPSInProgressCallBack(xml);
		}
		ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
		ajaxObj.sendRequest("wpsstate.php", "dummy=dummy");
	},
	WPSInProgressCallBack: function(xml)
	{
		var self = this;
		var cnt = xml.Get("/wpsstate/count");
		
		for (var i=1; i<=cnt; i++)
		{
			var state=xml.Get("/wpsstate/phyinf:"+i+"/state");
			if (state==="WPS_SUCCESS")
				break;
		}
		if (state=="WPS_IN_PROGRESS" || state=="")
			this.wps_timer = setTimeout('PAGE.WPSInProgress()',2000);
		else
			this.ShowWPSMessage(state);
	},
	WPSCountDown: function()
	{
		var time = parseInt(OBJ("ct").innerHTML, 10);
		if (time > 0)
		{
			time--;
			this.cd_timer = setTimeout('PAGE.WPSCountDown()',1000);
			OBJ("ct").innerHTML = time;
		}
		else
		{
			clearTimeout(this.cd_timer);
			this.ShowWPSMessage("WPS_NONE");
		}
	}, 
	ShowWpsDisabled: function()
	{
		for (var i=0; i<this.stages.length; i++)
		{
			OBJ(this.stages[i]).style.display = "none";
		}
		OBJ("wiz_stage_wps_disabled").style.display = "block";
	}
}


function DisableWPS()
{
	OBJ("pincode").disabled = true;
	SetButtonDisabled("b_send", true);
}

function SetButtonDisplayNone(name, display)
{
	var button = document.getElementsByName(name);
	for (i=0; i<button.length; i++)
	{
		button[i].style.display = display;
	}
}

function SetButtonDisabled(name, isDisable)
{
	var button = document.getElementsByName(name);
	for (i=0; i<button.length; i++)
		button[i].disabled = isDisable;
}
</script>
