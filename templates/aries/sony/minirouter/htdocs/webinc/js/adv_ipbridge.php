<script type="text/javascript">

function Page() {}
Page.prototype =
{
	services: "DEVICE.LAYOUT,DEVICE.PASSTHROUGH",
	OnLoad: function()
	{
		if (!this.rgmode)
		{
			BODY.DisableCfgElements(true);
		}
	},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		switch (code)
		{
		case "OK":
			if (!(COMM_Equal(OBJ("ipv6_passthrough").getAttribute("modified"), true)))
			{
				BODY.OnReload();
			}
			break;
		return true;
		}
	},
	
	InitValue: function(xml)
	{
		PXML.doc = xml;
		var b = PXML.FindModule("DEVICE.PASSTHROUGH");
		if (!b)
		{
			BODY.ShowAlert("<?echo "InitIPbridge() ERROR!!!";?>");
			return false;
		}
		if (XG(b+"/device/passthrough/ipv6")==="1")
			OBJ("ipv6_passthrough").checked = true;
		else
			OBJ("ipv6_passthrough").checked = false;
			
		return true;
	},
	PreSubmit: function()
	{
		var Pthrough = PXML.FindModule("DEVICE.PASSTHROUGH")+"/device/passthrough/ipv6";
		PXML.ActiveModule("DEVICE.PASSTHROUGH");
		
		if (COMM_Equal(OBJ("ipv6_passthrough").getAttribute("modified"), "true"))
		{
			if (OBJ("ipv6_passthrough").checked)
				XS(Pthrough, "1");
			else
				XS(Pthrough, "null");
		}
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

	?>
	
	
};
</script>
