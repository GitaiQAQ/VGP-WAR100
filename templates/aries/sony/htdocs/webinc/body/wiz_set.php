<?
function wiz_buttons()
{

	echo '  <div class="emptyline_w"></div>\n'.
		 '	<div class="centerline_w">\n'.
		 '		<input type="button" name="b_pre" value="'.I18N("h","Prev").'" onClick="PAGE.OnClickPre();" />&nbsp;&nbsp;\n'.
		 '		<input type="button" name="b_next" value="'.I18N("h","Next").'" onClick="PAGE.OnClickNext();" />&nbsp;&nbsp;\n'.
		 '		<input type="button" name="b_exit" value="'.I18N("h","Cancel").'" onClick="PAGE.OnClickCancel();" />&nbsp;&nbsp;\n'.
		 '		<input type="button" name="b_send" value="'.I18N("h","Connect").'" onClick="BODY.OnSubmit();" disabled="true" />&nbsp;&nbsp;\n'.
		 '	</div>\n'.
		 '	<div class="emptyline_w"></div>';
}
?>
<form id="mainform" onsubmit="return false;">

<!-- Start of Stage Ethernet -->
<div id="stage_interc" style="display:none;">
	<div class="blackbox">
	<h1><?echo I18N("h","Basic Setting Wizard");?></h1>
		<div class="graybox">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Operation Mode");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="operation_mode_w" onChange="PAGE.OnChangeAPMode_w();">
						<option value="auto"><?echo I18N("h","Auto-detect mode");?></option>
						<option value="ap"><?echo I18N("h","Access Point mode");?></option>
						<option value="rg"><?echo I18N("h","Router Mode");?></option>
					</select>
				</span>
			</div>
			<div id="RGmode_button">
				<? wiz_buttons();?>
			</div>
		
		
			<div id="RGmode_type" style="display:none;">
				<div class="textinput_l" style="height:70px">
					<span class="name"><?echo I18N("h","WAN Connection Method");?></span>
				</div>
				<div class="textinput_r" style="height:70px;width:98%">
					<div class="wiz-l1">
						<input name="wan_mode" type="radio" value="DHCP" onClick="PAGE.OnChangeWanType(this.value);" />
						<?echo I18N("h","DHCP");?>
					</div>
					<div class="wiz-l1">
						<input name="wan_mode" type="radio" value="PPPoE" onClick="PAGE.OnChangeWanType(this.value);" />
						<?echo I18N("h","PPPoE");?>
					</div>
					<div class="wiz-l1">
						<input name="wan_mode" type="radio" value="STATIC" onClick="PAGE.OnChangeWanType(this.value);" />
						<?echo I18N("h","Static IP");?>
					</div>
				</div>
			<? wiz_buttons();?>
			</div>
		</div>
	</div>
</div>
<!-- End of Stage Ethernet -->
<!-- Start of Stage Ethernet WAN Settings -->
<div id="stage_ether_cfg" style="display:none;">
	<input id="ppp4_timeout" type="hidden" />
	<input id="ppp4_mode" type="hidden" />
	<input id="ppp4_mtu" type="hidden" />
	<input id="ipv4_mtu" type="hidden" />
	<!-- Start of DHCP -->
	<div id="DHCP">
		<div class="blackbox">
			<div class="graybox">
				<? wiz_buttons();?>
			</div>
		</div>
	</div>
	<!-- End of DHCP -->
	<!-- Start of PPPoE -->
	<div id="PPPoE">
		<div class="blackbox">
			<h1><?echo I18N("h","PPPoE Settings");?></h1>
			<div class="graybox">
				<div id="address_mode" style="display:none;">
					<div class="textinput_l">
						<span class="name"><?echo "Address Mode";?></span>
					</div> 
					<div class="textinput_r" style="width:98%">
						<span class="value">
							<input name="wiz_pppoe_conn_mode" type="radio" value="dynamic" checked onChange="PAGE.OnChangePPPoEMode();" />
								<!--<?echo "Dynamic IP";?>-->
							<span class="value">
								<input name="wiz_pppoe_conn_mode" type="radio" value="static" onChange="PAGE.OnChangePPPoEMode();" />
								<!--<?echo "Static IP";?>-->
							</span>
						</span>
					</div>
				</div>
				<div id="ip_address_1" style="display:none;">
					<div class="textinput_l">
						<span class="name"><?echo "IP Address";?></span>
					</div> 
					<div class="textinput_r">
						<span class="value">
							<input id="wiz_pppoe_ipaddr" type="text" size="20" maxlength="15" />
						</span>
					</div>
				</div>
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","User Name");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_pppoe_usr" type="text" size="20" maxlength="63" />
					</span>
				</div>
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","Password");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_pppoe_passwd" type="text" size="20" maxlength="63" />
					</span>
				</div>
			<? wiz_buttons();?>
			</div>
		</div>
	</div>
	<!-- End of PPPoE -->
	<!-- Start of STATIC -->
	<div id="STATIC">
		<div class="blackbox">
			<h1><?echo I18N("h","Static IP Settings");?></h1>
			<div class="graybox">
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","IP Address");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_static_ipaddr" type="text" size="20" maxlength="15" />
					</span>
				</div>
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","Subnet Mask");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_static_mask" type="text" size="20" maxlength="15" />
					</span>
				</div>
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","Default Gateway");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_static_gw" type="text" size="20" maxlength="15" />
					</span>
				</div>
					
				<!--20130103 jack add DNS setting-->
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","Primary DNS Server");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_static_dns1" type="text" size="20" maxlength="15" />
					</span>
				</div>
				<div class="textinput_l">
					<span class="name"><?echo I18N("h","Secondary DNS Server");?></span>
				</div> 
				<div class="textinput_r" style="width:98%">
					<span class="value">
						<input id="wiz_static_dns2" type="text" size="20" maxlength="15" />
					</span>
				</div>
				
			<? wiz_buttons();?>	
			</div>
		</div>
	</div>
	<!-- End of STATIC -->

</div>
<!-- End of Stage Ethernet WAN Settings -->
<!-- Start of Stage Password -->
<div id="stage_passwd" class="blackbox" style="display:none;">
	<h1><?echo I18N("h","Admin Password Settings");?></h1>
	<div class="graybox">
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Password");?></span>
		</div> 
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="wiz_passwd" type="password" size="20" maxlength="15" />
			</span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Re-enter the Password");?></span>
		</div> 
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="wiz_passwd2" type="password" size="20" maxlength="15" />
			</span>
		</div>
	<? wiz_buttons();?>
	</div>
</div>
<!-- End of Stage Password -->
<!-- Start of Stage Finish -->
<div id="stage_finish" class="blackbox" style="display:none;">
	<h1><?echo I18N("h","End Wizard");?></h1>
	<div class="graybox">
		<? wiz_buttons();?>
	</div>
</div>
<!-- End of Stage Finish -->
<div class="emptyline" style="height:170px"></div>
</form>
