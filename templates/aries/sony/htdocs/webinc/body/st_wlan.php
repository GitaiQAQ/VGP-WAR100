<form id="mainform" onsubmit="return false;">
<div id="div_24G" name="div_24G" class="blackbox">
	<h1><?echo I18N("h","Wireless Connection");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","Wireless Client List");?><span style="display:none;" id="client_cnt"></span></h2>
		<div class="centerline">
			<table id="client_list" class="general" style="width:100%">
			<tr style="font-size:12px">
				<th width="113px"><?echo I18N("h","MAC Address");?></th>
				<th width="93px"><?echo I18N("h","IP Address");?></th>
				<th width="103px"><?echo I18N("h","Wireless Band");?></th>
				<th width="125px"><?echo I18N("h","Data Rate");?> (Mbps)</th>
				<th><?echo I18N("h","Signal Strength");?> (%)</th>
			</tr>
			</table>
		</div>
	</div>
</div>

<div class="emptyline" style="height:170px"></div>
</form>
