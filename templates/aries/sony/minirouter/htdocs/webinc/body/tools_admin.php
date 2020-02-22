<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","Admin Password Settings");?></h1>
	<div class="graybox">
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Password ");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value"><input id="admin_p1" type="password" size="20" maxlength="15" /></span>
		</div>
		<div class="textinput_l">
			<span class="name"><?echo I18N("h","Re-enter the Password");?></span>
		</div>
		<div class="textinput_r" style="width:98%">
			<span class="value"><input id="admin_p2" type="password" size="20" maxlength="15" /></span>
		</div>
		<hr>
		<p align="right">
		<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
		<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>
	</div>
</div>

<div class="emptyline" style="height:170px"></div>
<form>
