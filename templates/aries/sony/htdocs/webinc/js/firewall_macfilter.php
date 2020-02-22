<?include "/htdocs/phplib/inf.php";?>

<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "WIFI.PHYINF,PHYINF.WIFI",
	OnLoad:   function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result){return false;},
	
	InitValue: function(xml)
	{
		PXML.doc = xml;
		if (!this.InitMACFILTER()) return false;
		return true;
	},

	PreSubmit: function()
	{
		if (!this.PreMACFILTER()) return null;
		return PXML.doc;
	},

	SetWps: function(string)
	{
		var phyinf 		= GPBT(this.wifi_module, "phyinf", "uid","BAND24G-1.1", false);
		var wifip 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", XG(phyinf+"/wifi"), false);
		
		if(this.dual_band)
		{
			var phyinf2 	= GPBT(this.wifi_module, "phyinf", "uid","BAND5G-1.1", false);
			var wifip2 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", XG(phyinf2+"/wifi"), false);	
		}
		
		if(string=="enable")
		{
			XS(wifip+"/wps/enable", "1");
			if(this.dual_band) XS(wifip2+"/wps/enable", "1");
		}
		else
		{
			XS(wifip+"/wps/enable", "0");
			if(this.dual_band) XS(wifip2+"/wps/enable", "0");			
		}
	},

	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	
	//macfp : null,
	InitMACFILTER: function()
	{
		this.dual_band 		= COMM_ToBOOL('<?=$FEATURE_DUAL_BAND?>');
		this.wifi_module	= PXML.FindModule("WIFI.PHYINF");
		var wifi1p			= GPBT(this.wifi_module+"/wifi", "entry", "uid", "WIFI-1", false);

		if (XG(wifi1p+"/acl/policy") !== "")	
		{
			OBJ("mode").value = XG(wifi1p+"/acl/policy");
		}	
		else
		{					
			OBJ("mode").value = "DISABLE";
		}	

		/* load table content */
		for(i=1; i<=<?=$MAC_FILTER_MAX_COUNT?>; i++)
		{		
			if(OBJ("mode").value == "DISABLED")
			{
				OBJ("uid_"+i).disabled	= true;
				OBJ("en_"+i).disabled	= true;
				OBJ("mac_"+i).disabled	= true;
				OBJ("client_list_"+i).disabled     = true;
				OBJ("arrow_"+i).disabled           = true;
			}	
			else
			{
				OBJ("uid_"+i).disabled	= false;
				OBJ("en_"+i).disabled	= false;
				OBJ("mac_"+i).disabled	= false;
				OBJ("client_list_"+i).disabled     = false;				
				OBJ("arrow_"+i).disabled           = false;
			}
			var b = wifi1p+"/acl/entry:"+i;
			OBJ("uid_"+i).value	= XG(b+"/uid");
			OBJ("en_"+i).checked	= XG(b+"/enable")==="1";
			OBJ("mac_"+i).value	= XG(b+"/mac");
			OBJ("client_list_"+i).value  = "";			
		}
		return true;
	},
	
	PreMACFILTER: function()
	{
		/* wps 2.0 spec, if mac filter enabled, wps must be disabled, */
		var wifi1p 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", "WIFI-1", false);
		var wifi2p 		= GPBT(this.wifi_module+"/wifi", "entry", "uid", "WIFI-2", false);
		var wps_enable  = COMM_ToBOOL(XG(wifi1p+"/wps/enable"));

		if(wps_enable && OBJ("mode").value != "DISABLED")
		{
			if(confirm('<?echo I18N("j", "WPS must be disabled in order to use MAC address filtering. Proceed?");?>'))
				this.SetWps("disable");
			else 
				return false;
		}
	
		XS(wifi1p+"/acl/policy", OBJ("mode").value);
		XS(wifi2p+"/acl/policy", OBJ("mode").value);

		var old_count = XG(wifi1p+"/count");
		var cur_count = 0;
		/* delete the old entries
		 * Notice: Must delte the entries from tail to head */
		while(old_count > 0)
		{
			XD(wifi1p+"/acl/entry:"+old_count);
			old_count -= "1";
		}

		/* update the entries */
		for (var i=1; i<=<?=$MAC_FILTER_MAX_COUNT?>; i+=1)
		{
			/* if the mac field is empty, it means to remove this entry,
			 * so skip this entry. */
			if (OBJ("mac_"+i).value!=="")
			{
				var mac = this.GetMAC(OBJ("mac_"+i).value);
				for (var j=1; j<=6; j++)
				{
					if (mac[j].length == "1")
					mac[j] = "0"+mac[j];
				}
				OBJ("mac_"+i).value = mac[1].toUpperCase()+":"+mac[2].toUpperCase()+":"+mac[3].toUpperCase()+":"+mac[4].toUpperCase()+":"+mac[5].toUpperCase()+":"+mac[6].toUpperCase();

				cur_count+=1;
				var b = wifi1p+"/acl/entry:"+cur_count;

				XS(b+"/uid",			"MACF-"+i);
				XS(b+"/enable",			OBJ("en_"+i).checked ? "1" : "0");
				XS(b+"/mac",			OBJ("mac_"+i).value);
				b = wifi2p+"/acl/entry:"+cur_count;
				XS(b+"/uid",			"MACF-"+i);
				XS(b+"/enable",			OBJ("en_"+i).checked ? "1" : "0");
				XS(b+"/mac",			OBJ("mac_"+i).value);
			}
		}

		XS(wifi1p+"/acl/count", cur_count);
		XS(wifi2p+"/acl/count", cur_count);

		//PXML.ActiveModule("WIFI");
		PXML.CheckModule("WIFI.PHYINF", null, null, "ignore");
		
		return true;
	},

	OnClickArrowKey: function(index)
	{
		var dhcp_client = OBJ("client_list_"+index);

		if (dhcp_client.value === "")
		{
			BODY.ShowAlert("<?echo I18N("j","Please select a computer name first.");?>");
			return false;
		}

		OBJ("mac_"+index).value = dhcp_client.value;
	},
	GetMAC: function(m)
	{
		var myMAC=new Array();
		if (m.search(":") != -1)	var tmp=m.split(":");
		else				var tmp=m.split("-");
		for (var i=0;i <= 6;i++)
		myMAC[i]="";
		if (m != "")
		{
			for (var i=1;i <= tmp.length;i++)
			myMAC[i]=tmp[i-1];
			myMAC[0]=m;
		}
		return myMAC;
	},
	OnChangeMode: function()
	{
		/* load table content */
		for(i=1; i<=<?=$MAC_FILTER_MAX_COUNT?>; i++)
		{		
			if(OBJ("mode").value == "DISABLED")
			{
				OBJ("uid_"+i).disabled	= true;
				OBJ("en_"+i).disabled	= true;
				OBJ("mac_"+i).disabled	= true;
				OBJ("client_list_"+i).disabled     = true;
				OBJ("arrow_"+i).disabled           = true;
			}	
			else
			{
				OBJ("uid_"+i).disabled	= false;
				OBJ("en_"+i).disabled	= false;
				OBJ("mac_"+i).disabled	= false;
				OBJ("client_list_"+i).disabled     = false;
				OBJ("arrow_"+i).disabled           = false;
			}	
		}
	},
	
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
</script>
