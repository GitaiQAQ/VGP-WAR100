#!/bin/sh
<?
include "/htdocs/phplib/xnode.php";

$UID="BAND24G";
$phyp1	= XNODE_getpathbytarget("", "phyinf", "uid", $_GLOBALS["UID"]."-1.1", 0);
$wifi1	= XNODE_getpathbytarget("/wifi", "entry", "uid", query($phyp1."/wifi"), 0);

if(query($wifi1."/wps/enable")==1)
{
	echo "event WPS.INPROGRESS\n";
}
?>
