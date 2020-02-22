<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","LAN");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","Address Settings");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","DHCP Client Enabled");?></span>
		</div>
    	<div class="textinput_r" style="width:98%">
			<span class="value"><input id="dhcpc_en" type="checkbox" onClick="PAGE.OnClickDHCPC_EN();" /></span>
		</div>
    	<!--<div class="textinput_r" style="width:98%">
			<span class="value"><input id="dhcpsvr" type="checkbox" onClick="PAGE.OnClickDHCPSvr();" /></span>
		</div>-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","IP Address");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value"><input id="ipaddr" type="text" size="20" maxlength="15" /></span>
		</div>
	</div>
</div>
<div class="blackbox">
	<div class="graybox">
		<h2><?echo I18N("h","DHCP Server Settings");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Always broadcast");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<input name="broadcast" type="checkbox" id="broadcast" />
			</span>
		</div>
		<hr>
		<p align="right">
		<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
		<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>
	</div>
</div>

</form>
