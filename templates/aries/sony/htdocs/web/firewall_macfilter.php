HTTP/1.1 200 OK

<?
/* The variables are used in js and body both, so define them here. */
$MAC_FILTER_MAX_COUNT = query("/wifi/entry:1/acl/max");
if ($MAC_FILTER_MAX_COUNT == "") $MAC_FILTER_MAX_COUNT = 8;
if ($FW_MAX_COUNT == "") $FW_MAX_COUNT = 8;

/* necessary and basic definition */
$TEMP_MYNAME    = "firewall_macfilter";
$TEMP_MYGROUP   = "wireless";
$TEMP_STYLE		= "complex";
include "/htdocs/webinc/templates.php";
?>
