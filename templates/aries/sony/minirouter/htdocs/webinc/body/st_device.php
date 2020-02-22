<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","Connection Status");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","Firmware Version");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Firmware Version");?></span>
		</div>	
		<div class="textinput_r" style="width:98%">
			<span class="value"><?echo query("/runtime/device/firmwareversion").' '.query("/runtime/device/firmwarebuilddate");?></span>
		</div>
	</div>	
</div>
<div class="blackbox" id="wan_ethernet_block" style="display:none;">
	<div class="graybox">
	    <h2><?echo I18N("h","WAN");?></h2>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","Connection Method");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wantype"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","LAN Cable Connection");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wancable"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","Network Status");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_networkstatus"></span>
	    </div>
		
		<div class="textinput_l">
	        <span class="name"><?echo I18N("h","Connection Uptime");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_connection_uptime"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","MAC Address");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
		    <span class="value" id="st_wan_mac"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name" id= "name_wanipaddr"></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wanipaddr"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","Subnet Mask");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wannetmask"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name" id= "name_wangateway"></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wangateway"></span>
	    </div>
	    <div class="textinput_l">
	        <span class="name"><?echo I18N("h","Primary DNS Server");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wanDNSserver"></span>
	    </div>
	    <div class="textinput_l" >
	        <span class="name"><?echo I18N("h","Secondary DNS Server");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
	        <span class="value" id="st_wanDNSserver2"></span>
	    </div>    
	</div> 
</div>

<div class="blackbox">
	<div class="graybox">
		<h2><?echo I18N("h","Wireless LAN");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","MAC Address");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value"><?echo query("/runtime/devdata/wlanmac");?></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Wireless Band");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_80211mode"></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Channel Width");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_Channel_Width"></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Channel");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_Channel"></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","SSID");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_SSID"></span>
		</div>
		<!--Arragned by Builder-->
		<!--+++-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Security Type");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_security"></span>
		</div>
		<!--+++-->
		<!--20121113 jack add ssid2-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","2nd SSID");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_SSID2"></span>
		</div>
		<!--Arragned by Builder to display 2nd SSID Security-->
		<!--+++-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","2nd SSID Security Type");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_security2"></span>
		</div>
		<!--+++-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","WPS (Wi-Fi Protected Setup)");?></span>
	    </div> 
	    <div class="textinput_r" style="width:98%">
			<span class="value" id="st_WPS_status"></span>
		</div>
	</div>	
</div>	

</form>
