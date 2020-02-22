<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "PHYINF.WAN-1,UPNP.LAN-1,WIFI.PHYINF,DEVICE",
	OnLoad: function()
	{
		if (!this.rgmode)
			OBJ("upnp").disabled = true;
	},
	OnUnload: function() {},
	OnSubmitCallback: function ()	{},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		var upnp = PXML.FindModule("UPNP.LAN-1");
		var phy = PXML.FindModule("PHYINF.WAN-1");
		var wanphyuid = PXML.doc.Get(phy+"/inf/phyinf");
		var wan = PXML.doc.GetPathByTarget(phy, "phyinf", "uid", wanphyuid, false);
		this.DEVICEp = PXML.FindModule("DEVICE");
	
		if (upnp==="" ||  wan==="")
		{ alert("InitValue ERROR!"); return false; }

		OBJ("upnp").checked = (XG(upnp+"/inf/upnp/count") == 1);
		return true;
	},
	PreSubmit: function()
	{
		if (this.rgmode)
		{
			var upnp = PXML.FindModule("UPNP.LAN-1");
			var phy = PXML.FindModule("PHYINF.WAN-1");
			var wanphyuid = PXML.doc.Get(phy+"/inf/phyinf");
			var wan = PXML.doc.GetPathByTarget(phy, "phyinf", "uid", wanphyuid, false);
			XS(upnp+"/inf/upnp/count",	OBJ("upnp").checked ? "1":"0");		
			if(OBJ("upnp").checked)
			{
				XS(upnp+"/inf/upnp/entry:1", "urn:schemas-upnp-org:device:InternetGatewayDevice:1");			
			}
			else
			{
				XS(upnp+"/inf/upnp/entry:1", "");
			}
		}
		else
		{
			PXML.IgnoreModule("UPNP.LAN-1");
			PXML.IgnoreModule("PHYINF.WAN-1");
		}

		PXML.CheckModule("WIFI", null, "ignore", null);

		return PXML.doc;
	},
	IsDirty: null,
	DEVICEp:null,
	Synchronize: function() {},

	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,

}

</script>
