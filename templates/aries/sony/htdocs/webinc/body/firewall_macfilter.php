<form id="mainform" onsubmit="return false;">
<div class="blackbox">
	<h1><?echo I18N("h","Access Control");?></h1>
	<div class="graybox">
		<h2><?echo I18N("h","MAC Filtering Rules");?></h2>
		<div style="background-color:#212121">
		<p><?echo I18N("h","Configure MAC Filtering");?>
		
			<select id="mode" onchange="PAGE.OnChangeMode();">
				<option value="DISABLED"><?echo I18N("h","Disabled");?></option>
				<option value="DROP"><?echo I18N("h","Deny access");?></option>
				<option value="ACCEPT"><?echo I18N("h","Allow access");?></option>
			</select>
		</p>
		<p><?echo I18N("h","Target MAC Address");?>: <span id="rmd" style="display:none;"></span></p>
		<div class="centerline" align="center">
			<table id="" class="general">
			<tr  align="center">
				<td width="25px">&nbsp;</td>
				<td width="180px"><b><?echo I18N("h","MAC Address");?></b></td>
				<td width="40px">&nbsp;</td>
				<td width="190px"><b><?echo I18N("h","DHCP Client List");?></b></td>
				
				<!--< ?if ($FEATURE_NOSCH!="1"){echo '<td width="188px"><b>'.I18N("h","Schedule").'</b></td>\n';}?>-->
			</tr>
<?
$INDEX = 1;
while ($INDEX <= $MAC_FILTER_MAX_COUNT) {	dophp("load", "/htdocs/webinc/body/firewall_macfilter_list.php");	$INDEX++; }
?>
			</table>
		</div>
		</div>
		<hr>
		<p align="right">
		<input type="button" value="<?echo I18N("h","Save");?>" onclick="BODY.OnSubmit();" />
		<input type="button" value="<?echo I18N("h","Cancel");?>" onclick="BODY.OnReload();" />
		</p>	
	</div>
</div>


</form>
