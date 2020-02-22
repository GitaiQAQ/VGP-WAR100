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
		if($referer == "")
			$referer = "./index.php";
		
		$title	= "ACTION ".$_GET["RESULT"];
		if($_GET["REASON"]=="ERR_REQ_TOO_LONG")
		{
			
			$message = "'".I18N("j","The action requested failed because the file uploaded too large.")."', ".
						"'<a href=\"".$referer."\">".I18N("j","Click here to return to the previous page.")."</a>'";
		}
		
		echo "\t\tvar msgArray = [".$message."];\n";
		echo "\t\tBODY.ShowMessage(\"".$title."\", msgArray);\n";
?>  },
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return true; },
	InitValue: function(xml) { return true; },
	PreSubmit: function() { return null; },
	IsDirty: null,
	
	ShowMessage: function(banner, msgArray)
	{
		var str = '<h1>'+banner+'</h1>';
		for (var i=0; i<msgArray.length; i++)
		{
			str += '<div class="emptyline"></div>';
			str += '<div class="centerline">'+msgArray[i]+'</div>';
		}
		str += '<div class="emptyline"></div>';
		OBJ("message").innerHTML = str;
		alert(msgArray);
	},

	Synchronize: function() {}
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
}
</script>