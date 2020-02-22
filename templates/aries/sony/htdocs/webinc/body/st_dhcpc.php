<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","DHCP Connection");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","DHCP Client List");?></h2>
		<div class="centerline" align="center">
			<table id="leases_list" class="general"  style="width:100%">
			<tr>
				<th width="195px"><?echo I18N("h","Host Name");?></th>
				<th width="100px"><?echo I18N("h","IP Address");?></th>
				<th width="105px"><?echo I18N("h","MAC Address");?></th>
				<th width="95px"><?echo I18N("h","Expired Time");?></th>
			</tr>
			</table>
		</div>
	</div>
</div>
<div class="emptyline" style="height:170px"></div>
</form>
