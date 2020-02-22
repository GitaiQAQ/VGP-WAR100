<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: null,
	OnLoad: function()
	{
<?
		include "/htdocs/phplib/trace.php";
		$referer = $_SERVER["HTTP_REFERER"];
		$t = 0;

		if ($_GET["PELOTA_ACTION"]=="fwupdate")
		{
			if ($_GET["RESULT"]=="SUCCESS")
			{
				$size	= fread("j","/var/session/imagesize"); if ($size == "") $size = "4000000";
				$fptime	= query("/runtime/device/fptime");
				$bt		= query("/runtime/device/bootuptime");
				$delay	= 10;
				$t		= $size/64000*$fptime/1000+$bt+$delay+20;
				$title	= I18N("j","Updating the firmware.");
				$message= '"'.I18N("j","Updating the firmware.").'", '.
						  '"'.I18N("j","It takes a while to update the firmware. The router will automatically restart after the update.").
						  ' '.I18N("j","Please DO NOT turn the router power off until reboot has completed.").'"';
			}
			else
			{
				$title = I18N("j","Failed to update the firmware.");
				$btn = "'<input type=\"button\" value=\"".I18N("j","OK")."\" onclick=\"self.location=\\'tools_firmware.php\\';\">'";
				if ($_GET["REASON"]=="ERR_NO_FILE")
				{
					$message = "'".I18N("j","No firmware file.")." ".I18N("j","Please select the correct firmware file and upload it again.")."', ".$btn;
				}
				else if ($_GET["REASON"]=="ERR_INVALID_SEAMA" || $_GET["REASON"]=="ERR_INVALID_FILE")
				{
					$message = "'".I18N("j","This firmware file is invalid.")." ".I18N("j","Please select the correct firmware file and upload it again.")."', ".$btn;
				}
				else if ($_GET["REASON"]=="ERR_ANOTHER_FWUP_PROGRESS")
				{
					$message = "'".I18N("j","The firmware has already been updated by a different user.")." ".I18N("j","If you want to make additional updates, please wait until the router has restarted and try again.")."', ".$btn;
				}
			}
		}
		else
		{
			TRACE_debug("Unknown action - ACTION=".$_POST["ACTION"]);
			$title = "Unknown ACTION!";
			$message = "'<a href=\"./index.php\">".I18N("j","Click here to return to the setting screen.")."</a>'";
			$referer = "./index.php";
		}

		echo "\t\tvar msgArray = [".$message."];\n";
		if ($t > 0)
			echo "\t\tBODY.ShowCountdown(\"".$title."\", msgArray, ".$t.", \"".$referer."\");\n";
		else
			echo "\t\tBODY.ShowMessage(\"".$title."\", msgArray);\n";
?>	},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return true; },
	InitValue: function(xml) { return true; },
	PreSubmit: function() { return null; },
	IsDirty: null,
	Synchronize: function() {}
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
}
</script>
