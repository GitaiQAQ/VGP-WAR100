<form id="fwup" action="fwup.cgi" method="post" enctype="multipart/form-data">
<div class="blackbox">
	<h1><?echo I18N("h","Firmware Update");?></h1>
	<div class="graybox">
		<input type="hidden" name="REPORT_METHOD" id="report_method"/>
		<input type="hidden" name="REPORT" id="report"/>
		<input type="hidden" name="DELAY" id="delay"/>
		<input type="hidden" name="PELOTA_ACTION" id="pelota_actuon"/>
		<div class="textinput_l" style="width:120px">
			<span class="name"><?echo I18N("h","Select the Firmware");?></span>
		</div>
		<div class="textinput_r"  style="width:98%">
			<span class="value">
				<input type="file" name="fw" size=25 />
				<input type="submit" value="<?echo I18N("h","Update");?>" />
			</span>
		</div>		
	</div>	
</div>
<div class="emptyline" style="height:170px"></div>
</form>

