<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","WPS");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","WPS(Wi-Fi Protected Setup) Settings");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Enable WPS");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value"><input id="en_wps" type="checkbox" onClick="PAGE.OnClickEnWPS();" /></span>
		</div>
		
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Lock WPS-PIN Setup");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value"><input id="lock_wifi_security" type="checkbox" /></span>
		</div>	
		
		<!--For normal condition, the default wireless security is WPA with PSK password, the wireless setting would never be unconfigured.-->
		<!--For WIFI verification, the default wireless security is none, the wireless setting could be unconfigured.-->
		<div id="rese_to_unconfigured" <? if(get("", "/runtime/devdata/wifiverify")!="1") echo "style='display:none;'"; ?>>
			<div class="textinput_l">
				<span class="name"><?echo "Reset to Unconfigured";?></span>
			</div> 
			<div class="textinput_r">
				<span class="value">
					<input id="reset_cfg" type="button" value="<?echo "Reset";?>"
						onClick="PAGE.OnClickResetCfg();" />
				</span>
			</div>
		</div>
	</div>
</div>
<div class="blackbox">
	<div class="graybox">
		<h2><?echo I18N("h","PIN Settings");?></h2>
		<div class="textinput_l">
			<span class="name">PIN</span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span id="pin" class="value"></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Connect your wireless device");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="go_wps" type="button" value="<?echo I18N("h","Connect");?>"
					onClick='self.location.href="./wiz_wps.php";' />
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
