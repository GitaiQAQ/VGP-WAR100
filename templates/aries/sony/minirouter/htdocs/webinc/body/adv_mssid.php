<form id="mainform" onsubmit="return false;">

<!-- ===================== 2.4Ghz, BG band ============================== -->
<div class="blackbox">
	<h1><?echo I18N("h","2nd SSID Settings");?></h1>
	<div class="graybox">
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Enable 2nd SSID");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="mssid_en" type="checkbox" onClick="PAGE.OnClickEnMSSID();"/>
			</span>
		</div>		
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","SSID");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="ssid" type="text" size="10" maxlength="32" />
			</span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","SSID Hidden");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="mssid_hidden" type="checkbox" />
			</span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Enable WMM (Wi-Fi Multimedia)");?></span>
		</div> 
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="en_wmm" type="checkbox" />
			</span>
		</div>	
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Security Type");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="security_type" onChange="PAGE.OnChangeSecurityType();">
					<option value=""><?echo I18N("h","None");?></option>
					<option value="wep"><?echo I18N("h","WEP");?></option>
					<option value="wpa_personal"><?echo I18N("h","WPA-Personal");?></option>
				</select>
			</span>
		</div>

		<div id="bsc_wlan_saveb">
			<hr>
			<p align="right">
				<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
				<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
			</p>
		</div>
	</div>
</div>	
		
<div id="wep" class="blackbox" style="display:none;">
	<div class="graybox">
		<h2><?echo I18N("h","WEP Settings");?></h2>

		<div class="textinput_l">
			<span class="name"><?echo I18N("h","WEP Key Length");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="wep_key_len" onChange="PAGE.OnChangeWEPKey();">
					<option value="64"><?echo I18N("h","64 bit (5 characters)");?></option>
					<option value="128"><?echo I18N("h","128 bit (13 characters)");?></option>
				</select>
			</span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Network Authentication");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="auth_type" onChange="PAGE.OnChangeWEPAuth();" style="width:330px">
					<!--<option value="OPEN">Open</option>-->
					<option value="WEPAUTO"><?echo I18N("h","Both (Open/Shared Key)");?></option>
					<option value="SHARED"><?echo I18N("h","Shared Key");?></option>
				</select>
			</span>
		</div>
		<div id="default_wep_key" style="display:none;">
			<div class="textinput_l">
				<span class="name"><?echo "Default WEP Key";?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="wep_def_key" onChange="PAGE.OnChangeWEPKey();">
						<option value="1">WEP Key 1</option>
						<option value="2">WEP Key 2</option>
						<option value="3">WEP Key 3</option>
						<option value="4">WEP Key 4</option>
					</select>
				</span>
			</div>
		</div>
		<div id="wep_64">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","WEP Key");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="wep_64_1" name="wepkey_64" type="text" size="15" maxlength="5" />
					<input id="wep_64_2" name="wepkey_64" type="text" size="15" maxlength="5" />
					<input id="wep_64_3" name="wepkey_64" type="text" size="15" maxlength="5" />
					<input id="wep_64_4" name="wepkey_64" type="text" size="15" maxlength="5" />
				</span>
			</div>
		</div>
		<div id="wep_128">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","WEP Key");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="wep_128_1" name="wepkey_128" type="text" size="31" maxlength="13" />
					<input id="wep_128_2" name="wepkey_128" type="text" size="31" maxlength="13" />
					<input id="wep_128_3" name="wepkey_128" type="text" size="31" maxlength="13" />
					<input id="wep_128_4" name="wepkey_128" type="text" size="31" maxlength="13" />
				</span>
			</div>
		</div>
			<hr>
			<p align="right">
				<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
				<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
			</p>
	</div>
</div>

<div id="box_wpa" class="blackbox" style="display:none;">
	<div class="graybox">
		<h2><?echo I18N("h","WPA Settings");?></h2>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","WPA Type");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="wpa_mode" onChange="PAGE.OnChangeWPAMode();">
					<option value="WPA+2"><?echo I18N("h","WPA/WPA2");?></option>
					<option value="WPA2"><?echo I18N("h","WPA2");?></option>
					<option value="WPA"><?echo I18N("h","WPA");?></option>
				</select>
			</span>
		</div>		
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Encryption");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="cipher_type">
					<option value="TKIP"><?echo I18N("h","TKIP");?></option>
					<option value="AES"><?echo I18N("h","AES");?></option>
					<option value="TKIP+AES"><?echo I18N("h","AES/TKIP");?></option>
				</select>
			</span>
		</div>
		<div id="gkupdate" style="display:none">
			<div class="textinput_l">
				<span class="name"><?echo "Group Key Update Interval";?></span>
			</div> 
    		<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="wpa_grp_key_intrv" type="text" size="20" maxlength="10" /> (seconds)
				</span>
			</div>	
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Pre-Shared Key");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<input id="wpa_psk_key" type="text" size="20" maxlength="63" />
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
