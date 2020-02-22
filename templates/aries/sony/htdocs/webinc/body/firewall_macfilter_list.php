		<tr>
			<td class="centered">
				<!-- the uid of MAC_FILTER -->
				<input type="hidden" id="uid_<?=$INDEX?>" value="">
				<input type="hidden" id="description_<?=$INDEX?>" value="">
				<input type="checkbox"id="en_<?=$INDEX?>"  />
			</td>
			<td><input type=text id="mac_<?=$INDEX?>" size=22 maxlength=17>
			</td>
			<td>
				<input type="button" id="arrow_<?=$INDEX?>" value="<<" class="arrow" onclick="PAGE.OnClickArrowKey(<?=$INDEX?>);" modified="ignore" />
			</td>
			
			<td>			
			<select id="client_list_<?=$INDEX?>" modified="ignore" style="width: 180px;">
				<option value=""><?echo I18N("h","Computer Name");?></option>
				<?
				$lanp = XNODE_getpathbytarget("/runtime", "inf", "uid", "LAN-1", false);
				if ($lanp != "")
				{
					foreach ($lanp."/dhcps4/leases/entry")
					echo '<option value="'.query("macaddr").'">'.query("hostname").'</option>';
				}
				?>
			</select>
			</td>
		</tr>
