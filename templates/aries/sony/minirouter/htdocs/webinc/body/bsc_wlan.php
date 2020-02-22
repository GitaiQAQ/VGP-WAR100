<?
include "/htdocs/phplib/wifi.php";
?>
<form id="mainform" onsubmit="return false;">
<!-- ===================== 2.4Ghz, BG band ============================== -->
<div id="div_24G" class="blackbox">
	<h1><?echo I18N("h","Basic Items");?></h1>
	<div class="graybox">
		<h2 id="div_24G_title"><?echo I18N("h","Wireless Network Settings");?></h2>
		<div id="div_24G_wlan">
			<div class="textinput_l">
				<span class="name" id="wlan24G_ssidname">
					<?echo I18N("h","SSID (Wireless Network Name)");?>
				</span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="ssid" type="text" size="20" maxlength="32" />
				</span>
			</div>
		</div> 
		<div id="div_24G_wlmode">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Wireless Band");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="wlan_mode" onChange="PAGE.OnChangeWLMode('');">
						<option value="b"><?echo I18N("h","B only");?></option>
						<option value="bg"><?echo I18N("h","B/G mixed");?></option>
						<option value="bgn"><?echo I18N("h","B/G/N mixed");?></option>
					</select>
				</span>
			</div>	
		</div>
		<div id="div_24G_autoch">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Enable Auto Channel Scan");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value"><input id="auto_ch" type="checkbox" onClick="PAGE.OnClickEnAutoChannel('');" /></span>
			</div>
		</div>
		<div id="div_24G_channel">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Wireless Channel");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="channel" onChange="PAGE.OnChangeChannel('');">
	<?
		$clist = WIFI_getchannellist();
		$count = cut_count($clist, ",");
		
		$i = 0;
		while($i < $count)
		{
			$ch = cut($clist, $i, ',');
			$str = $ch;
			//TRACE_error("11123");
			//for 2.4 Ghz
			if		($ch=="1")	 { $str = "1";  } 
			else if ($ch=="2")   { $str = "2";  } 
			else if ($ch=="3")   { $str = "3";  } 
			else if ($ch=="4")   { $str = "4";  } 
			else if ($ch=="5")   { $str = "5";  } 
			else if ($ch=="6")   { $str = "6";  } 
			else if ($ch=="7")   { $str = "7";  } 
			else if ($ch=="8")   { $str = "8";  } 
			else if ($ch=="9")   { $str = "9";  } 
			else if ($ch=="10")  { $str = "10"; }
			else if ($ch=="11")  { $str = "11"; }		
									
			else { $str = $ch ; }		
			
			echo '\t\t\t\t<option value="'.$ch.'">'.$str.'</option>\n';
			$i++;
		}
		
	?>				</select>
				</span>
			</div>
		</div>
		<div id="div_24G_txrate" style="display:none;">
			<div class="textinput_l">
				<span class="name"><?echo "Transmission Rate";?></span>
			</div> 
			<div class="textinput_r">
				<span class="value">
					<select id="txrate">
						<option value="-1"><?echo "Best"." ("."automatic".")";?></option>
					</select>
					(Mbit/s)
				</span>
			</div>
		</div>	
		<div id="div_24G_bw">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Channel Width");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="bw" onChange="PAGE.OnChangeBW('')";>
						<option value="20+40"><?echo I18N("h","20/40 MHz (Auto)");?></option>
						<option value="20"><?echo I18N("h","20 MHz");?></option>
					</select>
				</span>
			</div>
		</div> 
		<div id="div_24G_coexistence">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","20/40MHz Coexistence");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="coexist_enable" type="checkbox" />
				</span>
			</div>
		</div>		
		<div id="div_24G_visibility">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","SSID Hidden");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="ssid_hidden" type="checkbox" />
				</span>
			</div>
		</div>
		<div id="wmm_nable2">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Enable WMM (Wi-Fi Multimedia)");?></span>
			</div> 
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<input id="en_wmm" type="checkbox" />
				</span>
			</div>
		</div>
	</div>
</div>

<div class="blackbox">
	<div class="graybox">
		<h2 id="div_security_title"><?echo I18N("h","Wireless Security Settings");?></h2>
		<div class="textinput_l">
			<span class="name" <? if(query("/runtime/device/langcode")!="en") echo 'style="width: 28%;"';?> ><?echo I18N("h","Security Type");?></span>
		</div> 
    	<div class="textinput_r" style="width:98%">
			<span class="value">
				<select id="security_type" onChange="PAGE.OnChangeSecurityType('');">
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
				<select id="wep_key_len" onChange="PAGE.OnChangeWEPKey('');">
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
				<select id="auth_type" onChange="PAGE.OnChangeWEPAuth('');">
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
					<select id="wep_def_key" onChange="PAGE.OnChangeWEPKey('');">
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
				<select id="wpa_mode" onChange="PAGE.OnChangeWPAMode('');">
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
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Pre-Shared Key");?></span>
		</div> 
		<div class="textinput_r" style="width:98%">
			<span class="value"><input id="wpa_psk_key" type="text" size="20" maxlength="63" /></span>
			(<?echo I18N("h","8-63 characters");?>)
		</div>
		<hr>
		<p align="right">
			<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
			<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>		
	</div>
</div>

</form>
<div id="pad" style="display:none;">
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
	<div class="emptyline"></div>
</div>
