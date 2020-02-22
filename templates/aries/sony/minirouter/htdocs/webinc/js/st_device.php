<script type="text/javascript">
var S2I = function(str) { var num = parseInt(str, 10); return isNaN(num)?0:num;}
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
function WAN1DHCPRENEW()	{ SendEvent("WAN-1.DHCP.RENEW"); }
function WAN1DHCPRELEASE()	{ SendEvent("WAN-1.DHCP.RELEASE"); }
/*PPPoE or 3G*/
function WAN1PPPDIALUP()	{ SendEvent("WAN-1.PPP.DIALUP"); }
function WAN1PPPHANGUP()	{ SendEvent("WAN-1.PPP.HANGUP"); }
/*PPTP/L2TP*/
function WAN1COMBODIALUP()	{ SendEvent("WAN-1.COMBO.DIALUP"); }
function WAN1COMBOHANGUP()	{ SendEvent("WAN-1.COMBO.HANGUP"); }

function Page() {}
Page.prototype =
{
	services: "<?
		$layout = query("/runtime/device/layout");		
		echo "RUNTIME.TIME,RUNTIME.DEVICE,RUNTIME.PHYINF,WIFI.PHYINF,";
		if ($layout=="router")
			echo "INET.WAN-1,INET.LAN-1,RUNTIME.INF.WAN-1,INET.INF";
		else
			echo "RUNTIME.INF.BRIDGE-1";
		?>",
	OnLoad: function(){},
	OnUnload: function() {},
	OnSubmitCallback: function () {},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		
		if (!this.InitGeneral()) return false;		
		if (!this.InitWLAN("BAND24G-1.1","WIFI.PHYINF")) return false;
		
<?
		if ($layout=="router")
		{
			echo "\t\tif (!this.InitLAN()) return false;\n";
			echo "\t\tif (!this.InitWAN()) return false;\n";
		}
		else
		{
			echo "\t\tif (!this.InitBridge()) return false;\n";
		}
?>	

		return true;
	},
	PreSubmit: function() {},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////	
	//lanpcinfo: null,
	lanip: null,
	inetp: null,
	dhcps4: null,
	leasep: null,
	mask: null,
	ipdirty: false,
	InitGeneral: function ()
	{
        this.timep = PXML.FindModule("RUNTIME.TIME");
		this.uptime = XG  (this.timep+"/runtime/device/uptime");
		if (!this.uptime)
		{
			BODY.ShowAlert("InitGeneral() ERROR!!!");
			return false;
		}
		return true;
	},
	InitWLAN: function ( wlan_phyinf, wifi_phyinf )
	{
		var str_Aband;
		
		var wifi_phyinf_path = PXML.FindModule(wifi_phyinf);
		
		var phy_wlan_path = GPBT(wifi_phyinf_path, "phyinf", "uid", wlan_phyinf, false);
		var wifi_profile_name = XG(phy_wlan_path+"/wifi");
		var wifi_path = GPBT(wifi_phyinf_path+"/wifi", "entry", "uid", wifi_profile_name, false);

		var rphy = PXML.FindModule("RUNTIME.PHYINF");
		var rwlan1p = 	GPBT(rphy+"/runtime", "phyinf", "uid", wlan_phyinf, false);
		var freq = XG(phy_wlan_path+"/media/freq");
		
		if ((!wifi_path)||(!phy_wlan_path))
		{
			BODY.ShowAlert("InitWLAN() ERROR!!!");
			return false;
		}
		
		if(freq=="5")
			str_Aband = "_Aband";
		else 
			str_Aband = "";
			
		var IEEE80211mode =  XG(phy_wlan_path+"/media/wlmode");
		var string_bandwidth = "20MHZ";
		var check_bandwidth = 0;
        switch (IEEE80211mode)
		{
		   case "bgn":
		   		OBJ("st_80211mode"+str_Aband).innerHTML  = "<?echo I18N("j","B/G/N mixed");?>";
                check_bandwidth = 1;
				break;
		   case "bg":
		   		OBJ("st_80211mode"+str_Aband).innerHTML  = "<?echo I18N("j","B/G mixed");?>";
                check_bandwidth = 1;
				break;
		   case "b":
		   		OBJ("st_80211mode"+str_Aband).innerHTML  = "<?echo I18N("j","B only");?>";
				break;				
		}
		if (check_bandwidth==1)
		{
			string_bandwidth = XG  (phy_wlan_path+"/media/dot11n/bandwidth")== "20+40" ? "20/40MHz":"20MHz";
		}
		OBJ("st_Channel_Width"+str_Aband).innerHTML  = string_bandwidth;
		
		var host_channel = XG  (rwlan1p+"/media/channel");
		OBJ("st_Channel"+str_Aband).innerHTML  = host_channel ? host_channel : "N/A";
		
		if(typeof(OBJ("st_SSID"+str_Aband).innerText) !== "undefined") OBJ("st_SSID"+str_Aband).innerText = XG(wifi_path+"/ssid");
		else if(typeof(OBJ("st_SSID"+str_Aband).textContent) !== "undefined") OBJ("st_SSID"+str_Aband).textContent = XG(wifi_path+"/ssid");	
		else OBJ("st_SSID"+str_Aband).innerHTML = XG(wifi_path+"/ssid");
		//==20121123 jack add for ssid2==//
		this.InitSSID2( str_Aband );
		//==20121123 jack add for ssid2==//
        var string_WPS =  "<?echo I18N("j","Disabled  ");?>";
		if ( XG  (wifi_path+"/wps/enable") == "1")
		{
        	string_WPS =  "<?echo I18N("j","Enabled");?>";
		}
		OBJ("st_WPS_status"+str_Aband).innerHTML  = string_WPS;
        var string_security = "<?echo I18N("j","Disabled ");?>"; 
        if (XG  (wifi_path+"/encrtype") != "NONE")
		{
		    switch(XG  (wifi_path+"/authtype"))
			{
				case "WEPAUTO":
				case "OPEN":
				case "SHARED":
			    	string_security = "<?echo I18N("j","WEP");?>";
					break;
				case "WPAPSK":
			    	string_security = "<?echo I18N("j","WPA-PSK");?>";
					break;
				case "WPA2PSK":
			    	string_security = "<?echo I18N("j","WPA2-PSK");?>";
					break;
				case "WPA+2PSK":
			    	string_security = "<?echo I18N("j","WPA/WPA2-PSK");?>";
					break;
			}
		}
		OBJ("st_security"+str_Aband).innerHTML  = string_security;
		
		
	    return true;
	},
	InitWAN: function ()
	{
		var wan	= PXML.FindModule("INET.WAN-1");
		var rwan = PXML.FindModule("RUNTIME.INF.WAN-1");
		var rphy = PXML.FindModule("RUNTIME.PHYINF");
		var waninetuid = XG  (wan+"/inf/inet");
		var wanphyuid = XG  (wan+"/inf/phyinf");
		this.waninetp = GPBT(wan+"/inet", "entry", "uid", waninetuid, false);
		this.rwaninetp = GPBT(rwan+"/runtime/inf", "inet", "uid", waninetuid, false);      
		this.rwanphyp = GPBT(rphy+"/runtime", "phyinf", "uid", wanphyuid, false);     
		
		var base = PXML.FindModule("INET.INF");
		var waninetp2	= GPBT(base, "inf", "uid", "WAN-2", false);
				
		var str_networkstatus = str_Disconnected = "<?echo I18N("j","Disconnected");?>";
		var str_Connected = "<?echo I18N("j","Connected");?>";
		var wan_uptime = S2I(XG  (this.rwaninetp+"/uptime"));
		var system_uptime = S2I(XG  (this.timep+"/runtime/device/uptimes"));
		var wan_delta_uptime = (system_uptime-wan_uptime);
		var wan_uptime_sec = 0;
		var wan_uptime_min = 0;
		var wan_uptime_hour = 0;
		var wan_uptime_day = 0;
		var str_wanipaddr = str_wangateway = str_wanDNSserver = str_wanDNSserver2 = str_wannetmask ="0.0.0.0";
		var str_name_wanipaddr = "<?echo I18N("j","IP Address");?>";
		var str_name_wangateway = "<?echo I18N("j","Default Gateway");?>";

        var wancable_status=0;
		var wan_network_status=0;
		if ((!this.waninetp))
		{
			BODY.ShowAlert("InitWAN() ERROR!!!");
			return false;
		}

        if((XG  (this.rwanphyp+"/linkstatus")!="0")&&(XG  (this.rwanphyp+"/linkstatus")!=""))
        {
		   wancable_status=1;
		}
		OBJ("st_wancable").innerHTML  = wancable_status==1 ? str_Connected:str_Disconnected;

		if (XG  (this.waninetp+"/addrtype") == "ipv4")
		{
			if(XG(this.waninetp+"/ipv4/ipv4in6/mode")!="")
			{
				str_dslite_networkstatus  = str_Disconnected;
				if (wancable_status==1)
					wan_network_status=1;
					
				if (XG(rwan+"/runtime/inf/inet/ipv4/ipv4in6/remote")!="" && wancable_status==1) 
					str_dslite_networkstatus = str_Connected;
				}
			else
			{
				if(XG  ( this.waninetp+"/ipv4/static")== "1")
				{
			    		OBJ("st_wantype").innerHTML  = "<?echo I18N("j","Static IP");?>";//"Static IP";
			    		str_networkstatus  = wancable_status== 1 ? str_Connected:str_Disconnected;
			    		wan_network_status=wancable_status;
				}
				else
				{
			    		OBJ("st_wantype").innerHTML  = "<?echo I18N("j","DHCP");?>";//"DHCP Client";
					if ((XG  (this.rwaninetp+"/ipv4/valid")== "1")&& (wancable_status==1))
					{
						wan_network_status=1;
						str_networkstatus = str_Connected;
					}
				}
		  	}
		}
		else if (XG  (this.waninetp+"/addrtype") == "ppp4" || XG(this.waninetp+"/addrtype") == "ppp10")
		{		    					
			if(XG  ( this.waninetp+"/ppp4/over")== "eth")
			{				
				//if (XG(waninetp2+"/nat") === "NAT-1" && XG(waninetp2+"/active")==="1")					
					//OBJ("st_wantype").innerHTML  = "Russia PPPoE";
				//else
					OBJ("st_wantype").innerHTML  = "<?echo I18N("j","PPPoE");?>";//"PPPoE";
			}
			else
			    {OBJ("st_wantype").innerHTML  = "Unknow WAN type";}
			
			var connStat = XG(rwan+"/runtime/inf/pppd/status");    
			    
			if ((XG  (this.rwaninetp+"/ppp4/valid")== "1")&& (wancable_status==1))
			{
				wan_network_status=1;
			} 
		    switch (connStat)
	        {
	                case "connected":
						if (wan_network_status == 1)
							str_networkstatus=str_Connected;
						else
							str_networkstatus=str_Disconnected;
		            break;
		            case "":
	                case "disconnected":
                    {
		                str_networkstatus=str_Disconnected;
                        wan_network_status=0;
		            }
		            break;
	                case "on demand":
						str_networkstatus=str_Disconnected;
                        wan_network_status=0;
		            break;
	                default:
						str_networkstatus=str_Disconnected;
		                break;
	        }	
    
			 str_name_wanipaddr = "<?echo I18N("j","Local Address");?>";
		     str_name_wangateway = "<?echo I18N("j","Peer Address");?>";
		}

		if ((XG  (this.rwaninetp+"/addrtype") == "ipv4")&& wan_network_status==1)
		{
		    str_wanipaddr = XG  (this.rwaninetp+"/ipv4/ipaddr");
		    str_wangateway =  XG  (this.rwaninetp+"/ipv4/gateway");
		    
		    str_wannetmask =  COMM_IPv4INT2MASK(XG  (this.rwaninetp+"/ipv4/mask"));
		    str_wanDNSserver = XG  (this.rwaninetp+"/ipv4/dns:1");
		    str_wanDNSserver2 = XG  (this.rwaninetp+"/ipv4/dns:2");
		}
		else if ((XG  (this.rwaninetp+"/addrtype") == "ppp4")&& wan_network_status==1)
		{
		    str_wanipaddr = XG  (this.rwaninetp+"/ppp4/local");
		    str_wangateway = XG  (this.rwaninetp+"/ppp4/peer");
		    str_wannetmask = "255.255.255.255";
		    str_wanDNSserver = XG  (this.rwaninetp+"/ppp4/dns:1");
		    str_wanDNSserver2 = XG  (this.rwaninetp+"/ppp4/dns:2");
		}
		else if ((XG  (this.rwaninetp+"/addrtype") == "ppp10")&& wan_network_status==1)
		{
		    str_wanipaddr = XG  (this.rwaninetp+"/ppp4/local");
		    str_wangateway = XG  (this.rwaninetp+"/ppp4/peer");
		    str_wannetmask = "255.255.255.255";
		    str_wanDNSserver = XG  (this.rwaninetp+"/ppp4/dns:1");
		    str_wanDNSserver2 = XG  (this.rwaninetp+"/ppp4/dns:2");
		}

        if ((wan_network_status==1)&& (wan_delta_uptime > 0)&& (wan_uptime > 0))
		{
			wan_uptime_sec = wan_delta_uptime%60;
			wan_uptime_min = Math.floor(wan_delta_uptime/60)%60;
		 	wan_uptime_hour = Math.floor(wan_delta_uptime/3600)%24;
		 	wan_uptime_day = Math.floor(wan_delta_uptime/86400);
		 	if (wan_uptime_sec < 0)
		 	{
		 	    wan_uptime_sec=0;
		 	    wan_uptime_min=0;
		 	    wan_uptime_hour=0;
		 	    wan_uptime_day=0;
		 	}
		 	
		 	
		}
		if(XG(this.waninetp+"/ipv4/ipv4in6/mode")!="" && XG(this.waninetp+"/addrtype") == "ipv4")
		{
		
		}
		else
		{
			OBJ("st_networkstatus").innerHTML = str_networkstatus; 
			OBJ("name_wanipaddr").innerHTML = str_name_wanipaddr;
			OBJ("name_wangateway").innerHTML = str_name_wangateway;
			OBJ("st_wanipaddr").innerHTML  = str_wanipaddr;
			OBJ("st_wangateway").innerHTML  =  str_wangateway;
			OBJ("st_wanDNSserver").innerHTML  = str_wanDNSserver!="" ? str_wanDNSserver:"0.0.0.0";
			OBJ("st_wanDNSserver2").innerHTML  = str_wanDNSserver2!="" ? str_wanDNSserver2:"0.0.0.0";
			OBJ("st_wannetmask").innerHTML  =  str_wannetmask;
			OBJ("st_wan_mac").innerHTML  =  XG  (this.rwanphyp+"/macaddr");
		
			if (wan_uptime_day=="0")
				OBJ("st_connection_uptime").innerHTML= wan_uptime_hour+" "+"<?echo ":";?>"+" "+wan_uptime_min+" "+"<?echo ":";?>"+" "+wan_uptime_sec+" ";
			else
				OBJ("st_connection_uptime").innerHTML= wan_uptime_day+" "+"<?echo "D";?>"+" "+wan_uptime_hour+" "+"<?echo ":";?>"+" "+wan_uptime_min+" "+"<?echo ":";?>"+" "+wan_uptime_sec+" ";
			OBJ("wan_ethernet_block").style.display = "block";
		}
		
        /* If Open DNS function is enabled, the DNS server would be fixed. */
		if(XG(wan+"/inf/open_dns/type")==="advance")
		{
			OBJ("st_wanDNSserver").innerHTML  = XG(wan+"/inf/open_dns/adv_dns_srv/dns1");
			OBJ("st_wanDNSserver2").innerHTML  = XG(wan+"/inf/open_dns/adv_dns_srv/dns2");
		}
		else if(XG(wan+"/inf/open_dns/type")==="family")
		{
			OBJ("st_wanDNSserver").innerHTML  = XG(wan+"/inf/open_dns/family_dns_srv/dns1");
			OBJ("st_wanDNSserver2").innerHTML  = XG(wan+"/inf/open_dns/family_dns_srv/dns2");
		}
		else if(XG(wan+"/inf/open_dns/type")==="parent")
		{
			OBJ("st_wanDNSserver").innerHTML  = XG(wan+"/inf/open_dns/parent_dns_srv/dns1");
			OBJ("st_wanDNSserver2").innerHTML  = XG(wan+"/inf/open_dns/parent_dns_srv/dns2");
		}
				
		return true;
	},
	InitLAN: function()
	{
		var lan	= PXML.FindModule("INET.LAN-1");
		var inetuid = XG  (lan+"/inf/inet");		
		this.inetp = GPBT(lan+"/inet", "entry", "uid", inetuid, false);		
		if (!this.inetp)
		{
			BODY.ShowAlert("InitLAN() ERROR!!!");
			return false;
		}
		return true;
	},
	InitBridge: function()
	{
		var br = PXML.FindModule("RUNTIME.INF.BRIDGE-1");
		if (!br) { BODY.ShowAlert("InitBridge() ERROR !!!"); return false; }
		var wantype = XG(br+"/runtime/inf/inet/addrtype");
		var wantype_str = "Unknow WAN type";
		if (wantype=="ipv4")
		{
			if (XG(br+"/runtime/inf/udhcpc/inet")!="")
				wantype_str = "<?echo I18N("j","DHCP");?>";//"DHCP Client";
			else
				wantype_str = "<?echo I18N("j","Static IP");?>";//"Static IP";
		}
		else if (wantype=="ppp4")
			wantype_str = "<?echo I18N("j","PPPoE");?>";//"PPPoE";
		return true;
	},
	ResetXML: function()
	{
		COMM_GetCFG(
			false,
			PAGE.services,
			function(xml) {
				PXML.doc = xml;
			}
		);
	},
	//==20121123 jack add for ssid2==//
	InitSSID2: function( str_Aband )
	{
		var wifi_phyinf_path = PXML.FindModule("WIFI.PHYINF");
		var phy_wlan_path2 = GPBT(wifi_phyinf_path, "phyinf", "uid", "BAND24G-1.2", false);
		var wifi_profile_name2 = XG(phy_wlan_path2+"/wifi");
		var wifi_path2 = GPBT(wifi_phyinf_path+"/wifi", "entry", "uid", wifi_profile_name2, false);
		var string_security = "<?echo I18N("j","Disabled ");?>"; /* By Builder to display 2nd SSID security. */
		
		if(typeof(OBJ("st_SSID2").innerText) !== "undefined")
			OBJ("st_SSID2").innerText = XG(wifi_path2+"/ssid");
		else if(typeof(OBJ("st_SSID2").textContent) !== "undefined")
			OBJ("st_SSID2").textContent = XG(wifi_path2+"/ssid");	
		else 
			OBJ("st_SSID2").innerHTML = XG(wifi_path2+"/ssid");

        /* By Builder to display 2nd SSID security. */
		/*+++*/
		if (XG  (wifi_path2+"/encrtype") != "NONE")
		{
		    switch(XG  (wifi_path2+"/authtype"))
			{
				case "WEPAUTO":
				case "OPEN":
				case "SHARED":
			    	string_security = "<?echo I18N("j","WEP");?>";
					break;
				case "WPAPSK":
			    	string_security = "<?echo I18N("j","WPA-PSK");?>";
					break;
				case "WPA2PSK":
			    	string_security = "<?echo I18N("j","WPA2-PSK");?>";
					break;
				case "WPA+2PSK":
			    	string_security = "<?echo I18N("j","WPA/WPA2-PSK");?>";
					break;
			}
		}
		OBJ("st_security2"+str_Aband).innerHTML  = string_security;
		/*+++*/
	},
	//==20121123 jack add for ssid2==//
}

</script>
