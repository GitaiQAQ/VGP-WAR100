<?
include "/htdocs/phplib/trace.php"; 

function load_slp($lcode)
{
	if($lcode == "en")
	{
		sealpac("");
	}
	else
	{
		$slp = "/etc/sealpac/langpac/lang_".$lcode.".slp";
		if (isfile($slp)!="1") return 0;
		sealpac($slp);
	}
	
	
	if(query("/device/features/language") != $lcode)
	{
		set("/device/features/language", $lcode);
		event("DBSAVE");
	}
	else
	{
		set("/device/features/language", $lcode);
	}
	return 1;
}
function load_existed_slp()
{
	$slp = "/var/sealpac/sealpac.slp";
	$slp2 = "/etc/sealpac/en.slp";
	if (isfile($slp)!="1")
	{
		if (isfile($slp2)!="1")
		{
			/*unload language pack*/
			sealpac("");
		}
		else
		{
			sealpac($slp2);
		}
	}
	else
	{
		sealpac($slp);
	}
	return 1;
}
function set_LANGPACK()
{
	$lcode = $_GET["language"];
	$CountryCode = query("/runtime/devdata/countrycode");
	if ($lcode=="")
	{
		$lcode = query("/device/features/language");
	}
	
	
	if ($lcode=="auto")
	{

		if($CountryCode == "AP" || $CountryCode == "US" || $CountryCode == "GB")
		 $lcode = "en";
		else if ($CountryCode == "PA")//Spanish
		 $lcode = "es";
		else if ($CountryCode == "JP")
		 $lcode = "jp";
		else if ($CountryCode == "CN")
		 $lcode = "zhcn";
		else
		  $lcode = "en";
		
		
		$slp = "/etc/sealpac/langpac/lang_".$lcode.".slp";
		if (isfile($slp)!="1")
		{
			sealpac("");
			return "en";
		}
		else
		{
			sealpac($slp);
			return $lcode;
		}
	}
	else
	{
		if (load_slp($lcode) > 0) 
		{
			return $lcode;
		}
		else
		{
			sealpac("");//not use langpac
			return "en";
		}
		
	}
	//sealpac("/etc/sealpac/wizard/wiz_en.slp");	// Use system default language, en.
	sealpac("");
	return "en";
}
?>