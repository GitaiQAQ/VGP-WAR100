<form id="mainform" onsubmit="return false;">
<!-- 20121025 router/bridge mdoe will move to Operation mode -->
<!-- wan mode -->
<div class="blackbox">
	<h1><?echo I18N("h","WAN");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","Internet Connection Settings");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Connection Method");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="wan_ip_mode" onchange="PAGE.OnChangeWanIpMode();">
					<option value="dhcp"><?echo I18N("h","DHCP");?></option>																 
					<option value="static"><?echo I18N("h","Static IP");?></option>
					<option value="pppoe"><?echo I18N("h","PPPoE");?></option>
				</select>
			</span>
		</div>
	</div>
</div>

<!-- ipv4 settings: static & dhcp -->
<div class="blackbox" id="ipv4_setting" style="display:none">
	<div class="graybox">
		<!-- header -->
		<div id="box_wan_static" style="display:none">
			<h2><?echo I18N("h","Static IP Settings");?></h2>
		</div>
		<div id="box_wan_dhcp" style="display:none">
			<h2><?echo I18N("h","DHCP Settings");?></h2>
		</div>
		<!-- end of header -->
		<!-- static -->
		<div id="box_wan_static_body" style="display:none">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","IP Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="st_ipaddr" type="text" size="20" maxlength="15" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Subnet Mask");?></span>
				   		</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="st_mask" type="text" size="20" maxlength="15" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Default Gateway");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="st_gw" type="text" size="20" maxlength="15" /></span>
			</div>
		</div>
		<!-- dhcp -->
		<div id="box_wan_dhcp_body" style="display:none">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Always broadcast");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input type="checkbox" id="dhcpc_unicast" /></span>
			</div>
		</div><!-- box_wan_dhcp_body -->
		<!-- common -->
		<div id="box_wan_ipv4_common_body">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Primary DNS Server");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="ipv4_dns1" type="text" size="20" maxlength="15" />
					<span id="ipv4_dns1_optional" style="display:none"></span>
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Secondary DNS Server");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="ipv4_dns2" type="text" size="20" maxlength="15" />
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","MTU");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="ipv4_mtu" type="text" size="10" maxlength="4" /></span>
				(<?echo "576~1500";?>)
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","MAC Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="ipv4_macaddr" type="text" size="20" maxlength="17" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Clone Your PC's MAC Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="ipv4_mac_button" type="button" value="<?echo I18N("h","Copy");?>" onclick="PAGE.OnClickMacButton('ipv4_macaddr');" /></span>
			</div>
			<hr>
			<p align="right">
			<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
			<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
			</p>
		</div>
		<!-- end common -->
	</div>
</div>

<!-- ppp4 -->
<div class="blackbox" id="ppp4_setting" style="display:none">
	<div class="graybox">
		<!-- header -->
		<div id="box_wan_pppoe" style="display:none">
			<h2><?echo I18N("h","PPPoE Settings");?></h2>
		</div>
		<!-- end of header -->
		<!-- pppoe -->
		<div id="box_wan_pppoe_body" style="display:none">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","IP Address Setting Method");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input type="radio" id="pppoe_dynamic" name="pppoe_addr_type" onclick="PAGE.OnClickPppoeAddrType();"/><?echo I18N("h","Automatic Acquisition");?>
					<input type="radio" id="pppoe_static"  name="pppoe_addr_type" onclick="PAGE.OnClickPppoeAddrType();"/><?echo I18N("h","Static IP");?>
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","IP Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="pppoe_ipaddr" type="text" size="20" maxlength="15" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Username");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="pppoe_username" type="text" size="20" maxlength="63" />
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Password");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="pppoe_password" type="password" size="20" maxlength="63" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Re-enter the Password");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="confirm_pppoe_password" type="password" size="20" maxlength="63" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Service Name");?></span>
				   		</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="pppoe_service_name" type="text" size="30" maxlength="39" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Connection Timing");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input type="radio" id="pppoe_alwayson" name="pppoe_reconnect_radio" onclick="PAGE.OnClickPppoeReconnect();"/><?echo I18N("h","Always on");?>
					<input type="radio" id="pppoe_ondemand"	name="pppoe_reconnect_radio" onclick="PAGE.OnClickPppoeReconnect();"/><?echo I18N("h","Auto");?>
					<input type="radio" id="pppoe_manual"	name="pppoe_reconnect_radio" onclick="PAGE.OnClickPppoeReconnect();"/><?echo I18N("h","Manual");?>
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Connection to the Server");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input type="button" id="wan_ppp_connect" value="<?echo I18N("h","Connect");?>" onClick="PAGE.PPP_Connect();"/>&nbsp;&nbsp;
					<input type="button" id="wan_ppp_disconnect" value="<?echo I18N("h","Disconnect");?>" onClick="PAGE.PPP_Disconnect();"/>  
				</span>
			</div> 
			
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Maximum Idle Time");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="pppoe_max_idle_time" type="text" size="10" maxlength="5" />(<?echo I18N("h","minute(s)");?>)</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","DNS Server Address Setting Method");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="dns_isp"		type="radio" name="dns_mode" onclick="PAGE.OnClickDnsMode();"/><?echo I18N("h","Receive DNS from ISP");?>
					<input id="dns_manual"	type="radio" name="dns_mode" onclick="PAGE.OnClickDnsMode();"/><?echo I18N("h","Enter DNS Manually ");?>
				</span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Primary DNS Server");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="pppoe_dns1" type="text" size="20" maxlength="15" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Secondary DNS Server");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="pppoe_dns2" type="text" size="20" maxlength="15" />
				</span>
			</div>
		</div>
		<!-- box_wan_l2tp_body -->
		<!-- common -->
		<div id="box_wan_ppp4_comm_body">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","MTU");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="ppp4_mtu" type="text" size="10" maxlength="4" /></span>
				(<?echo "576~1492";?>)
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","MAC Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="ppp4_macaddr" type="text" size="20" maxlength="17" /></span>
			</div>
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Clone Your PC's MAC Address");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="mac_button" type="button" value="<?echo I18N("h","Copy");?>" onclick="PAGE.OnClickMacButton('ppp4_macaddr');" />
				</span>
			</div>
			<hr>
			<p align="right">
			<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
			<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
			</p>
			
		</div>
	</div>
</div><!--blackbox -->


</form>
