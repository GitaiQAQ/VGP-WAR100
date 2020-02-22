<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";

$path_a = "/runtime/freqrule/channellist/a";
$path_g = "/runtime/freqrule/channellist/g";

$c = query("/runtime/devdata/countrycode");
/* never set the channel list, so do it.*/
if (query($path_a)=="" || query($path_g)=="")
{
	/* map the region by country ISO name */
	$list_g = "1,2,3,4,5,6,7,8,9,10,11";	
	$list_a = "36,40,44,48,149,153,157,161,165";

	set($path_a, $list_a);
	set($path_g, $list_g);
}
?>
