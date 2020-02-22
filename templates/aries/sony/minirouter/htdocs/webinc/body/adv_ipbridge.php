<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","IPv6 Pass Through Settings");?></h1>
	<div class="graybox">
		<!--<h2><?echo I18N("h","IPv6 Pass Through");?></h2>-->
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Enable IPv6 Pass Through");?></span>
		</div>
    	<div class="textinput_r"  style="width:98%">
			<span class="value"><input id="ipv6_passthrough" type="checkbox"/></span>
		</div>
		<hr>
		<p align="right">
		<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
		<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>
	</div>
</div>
<div class="emptyline" style="height:170px"></div>
</form>

