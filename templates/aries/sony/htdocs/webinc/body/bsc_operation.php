<form id="mainform" onsubmit="return false;">

<!-- 20121025 this will move to Operation mode -->
<div class="blackbox">
<h1><?echo I18N("h","Operation Mode Settings");?></h1>
	<div class="graybox">
			<div class="textinput_l">
				<span class="name"><?echo I18N("h","Operation Mode");?></span>
			</div>
			<div class="textinput_r" style="width:98%">
				<span class="value">
					<select id="operation_mode">
						<option value="auto"><?echo I18N("h","Auto-detect mode");?></option>
						<option value="ap"><?echo I18N("h","Access Point mode");?></option>
						<option value="rg"><?echo I18N("h","Router Mode");?></option>
					</select>
				</span>			
			</div>
		<hr>
		<p align="right">
			<input type="button" id="topsave" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
			<input type="button" id="topcancel" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>
	</div>
</div>
<div class="emptyline" style="height:170px"></div>
</form>
