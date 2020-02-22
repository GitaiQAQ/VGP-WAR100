<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "INET.WAN-1,RUNTIME.PHYINF",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return false; },
	InitValue: function(xml)
	{
		Initial_textheight();//==20130322 add auto modify size
		OBJ("report_method").value = "301";
		OBJ("report").value = "tools_fw_rlt.php";
		OBJ("delay").value = "10";
		OBJ("pelota_actuon").value = "fwupdate";
		//OBJ("action").value = "langupdate";
		return true;
	},
	PreSubmit: function() { return null; },
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
}
// return true is brower is IE.
function is_IE()
{
	if (navigator.userAgent.indexOf("MSIE")>-1) return true;
	return false
}
</script>
