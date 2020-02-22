<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: null,
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return false; },
	InitValue: function(xml) { return true; },
	PreSubmit: function() { return null; },
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	OnClickFReset: function()
	{
		if (confirm("<?echo I18N("j","Are you sure you want to reset the router to its factory default settings?")."\\n".
					I18N("j","This will cause all current settings to be initialized.");?>"))
		{
			Service("FRESET");
		}
	},
}

function Service(svc)
{	
	var banner = "<?echo I18N("j","Rebooting the router...");?>";
	var msgArray = ["<?echo I18N("j","If you changed the IP address of the router, you will need to change the IP address in your browser in order to access the setting screen again.");?>"];
	var delay = 10;
	var sec = <?echo query("/runtime/device/bootuptime");?> + delay;
	var url = null;
	var ajaxObj = GetAjaxObj("SERVICE");
	if (svc=="FRESET")		url = "http://192.168.11.1/index.php";
	else if (svc=="REBOOT")	url = "http://<?echo $_SERVER["HTTP_HOST"];?>/index.php";
	else					return false;
	ajaxObj.createRequest();
	ajaxObj.onCallback = function (xml)
	{
		ajaxObj.release();
		if (xml.Get("/report/result")!="OK")
			BODY.ShowAlert("Internal ERROR!\nEVENT "+svc+": "+xml.Get("/report/message"));
		else
			BODY.ShowCountdown(banner, msgArray, sec, url);
	}
	ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
	ajaxObj.sendRequest("service.cgi", "EVENT="+svc);
}

</script>
