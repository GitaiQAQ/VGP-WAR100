<?
function wiz_buttons()
{
	echo '  <div class="emptyline_w"></div>\n'.
		 '	<div class="centerline" align="center">\n'.
		 '		<input type="button" name="b_exit" value="'.I18N("h","Cancel").'" onClick="PAGE.OnClickCancel();" />&nbsp;&nbsp;\n'.
		 '		<input type="button" name="b_send" value="'.I18N("h","Connect").'" onClick="PAGE.OnSubmit();" />\n'.
		 '	</div>\n'.
		 '	<div class="emptyline_w"></div>';
}
?>
<form id="mainform" onsubmit="return false;">

<!-- show this if wps is disabled -->
<div id="wiz_stage_wps_disabled" class="blackbox" style="display:none;">
	<div class="centerline" align="center">
		<input name="b_yes" value="Yes" onclick="self.location.href='./adv_wps.php';" type="button">
		<input name="b_no" value="No" onclick="self.location.href='./bsc_wlan.php';" type="button">
	</div>
</div>


<!-- Start of Stage 2 -->
<div id="wiz_stage_2_auto" class="blackbox" style="display:none;">
	<h1><?echo I18N("h","PIN");?></h1>
	<div class="graybox">
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","PIN");?></span>
		</div> 
		<div class="textinput_r" style="width:98%">
			<span class="value"><input id="pincode" type="text" size="20" maxlength="8"/></span>
		</div>
		<?wiz_buttons();?>
	</div>
</div>
<!-- End of Stage 2 -->
<!-- Message of Stage 2 -->
<div id="wiz_stage_2_msg" class="blackbox" style="display:none;">
	<h1><?echo I18N("h","Connect your wireless device");?></h1>
		<div class="graybox">
			<div><p style="color:white"><span id="msg"></span></p></div>
			<?wiz_buttons();?>
		</div>
</div>
<div class="emptyline" style="height:170px"></div>
<!-- Message of Stage 2 -->
</form>
