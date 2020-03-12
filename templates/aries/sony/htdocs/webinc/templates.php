<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/xnode.php";
include "/htdocs/webinc/feature.php";

include "/htdocs/phplib/lang.php";//include this file before using load_existed_slp()
/* Because wizard might using other language pack,
so load currently language pack before webpage start to shown. */
load_existed_slp();

//==20130103 jack langpack are at /etc/sealpac/wizard/
$lang_code = set_LANGPACK();
if($lang_code != "")  $lang = $lang_code;
else $lang = "en";

include "/htdocs/webinc/menu.php";		/* The menu definitions */


function is_label($group)
{
	if ($_GLOBALS["TEMP_MYGROUP"]==$group)
		echo ' class="label"';
}

function is_label_noecho($group)
{
	if ($_GLOBALS["TEMP_MYGROUP"]==$group)
		return ' class="label"';
}

function draw_menu($menuString, $menuLink, $delimiter)
{
	if($menuString != "")
	{
		$menuItems = cut_count($menuString,$delimiter);
		if($menuItems == 0) $menuItems = 1;
		$i = 0;
		while( $i < $menuItems )
		{
			if ($menuItems == 1)
			{
				$item = $menuString;
				$link = $menuLink;
			}
			else
			{
				$item = cut($menuString, $i, $delimiter);
				$link = cut($menuLink,   $i, $delimiter);
			}
			if ($link==$_GLOBALS["TEMP_MYNAME"].".php")
				echo '\t\t\t\t<a href="'.$link.'" onClick="return(ChangePage())">&nbsp;'.$item.'</a>\n';
			else
				echo '\t\t\t\t<a href="'.$link.'" onClick="return(ChangePage())">&nbsp;'.$item.'</a>\n';
				
			$i++;
		}
	}
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" /><!--not use IE8 compality-->
	<link href="/css/normalize.min.css" rel="stylesheet">
<?
	if ($TEMP_STYLE!="progress") echo '\t<link rel="stylesheet" href="/css/general.css" type="text/css">\n';			
	if ($TEMP_STYLE=="support") echo '\t<link rel="stylesheet" href="/css/support.css" type="text/css">\n';
?>	<meta http-equiv="Content-Type" content="no-cache">
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Wireless Router. | VGP-WAR100</title>		
<?
	//---For Widget, Joseph Chao
	if (query("/runtime/services/http/server/widget") > 0)
	{
		$salt = query("/runtime/widget/salt");
		echo "	<script>";
		echo "var salt = \"".$salt."\";";
		echo "</script>";
	}
	//---For Widget, Joseph Chao
?>		
	<script type="text/javascript" charset="utf-8" src="./js/comm.js"></script>
	<script type="text/javascript" charset="utf-8" src="./js/libajax.js"></script>
	<script type="text/javascript" charset="utf-8" src="./js/postxml.js"></script>
<?
if ($TEMP_STYLE=="complex" || $TEMP_STYLE=="support")
{
	echo '<script type="text/javascript" charset="utf-8" src="./js/menu.js"></script>\n';
}

	if($_GLOBALS["TEMP_MYNAME"]=="wiz_freset" || $_GLOBALS["TEMP_MYNAME"]=="wiz_mydlink")
	{
		echo '<script type="text/javascript" charset="utf-8" src="./js/position.js"></script>\n';
	}
	if (isfile("/htdocs/webinc/js/".$TEMP_MYNAME.".php")==1)
	{
		dophp("load", "/htdocs/webinc/js/".$TEMP_MYNAME.".php");
	}
?>
	<script type="text/javascript">
	var OBJ	= COMM_GetObj;
	var XG	= function(n){return PXML.doc.Get(n);};
	var XS	= function(n,v){return PXML.doc.Set(n,v);};
	var XD	= function(n){return PXML.doc.Del(n);};
	var XA	= function(n,v){return PXML.doc.Add(n,v);};
	var GPBT= function(r,e,t,v,c){return PXML.doc.GetPathByTarget(r,e,t,v,c);};
	var S2I	= function(str) {return isNaN(str)?0:parseInt(str, 10);}

	function TEMP_IsDigit(no)
	{
		if (no==""||no==null)
			return false;
		if (no.toString()!=parseInt(no, 10).toString())
			return false;

	    return true;
	}
	function TEMP_CheckNetworkAddr(ipaddr, lanip, lanmask)
	{
		if (lanip)
		{
			var network = lanip;
			var mask = lanmask;
		}
		else
		{
			var network = "<?$inf = XNODE_getpathbytarget("/runtime", "inf", "uid", "LAN-1", 0); echo query($inf."/inet/ipv4/ipaddr");?>";
			var mask = "<?echo query($inf."/inet/ipv4/mask");?>";
		}
		var vals = ipaddr.split(".");

		if (vals.length!=4)
			return false;

		for (var i=0; i<4; i++)
			if (!TEMP_IsDigit(vals[i]) || vals[i]>255)	return false;

		if (COMM_IPv4NETWORK(ipaddr, mask)!=COMM_IPv4NETWORK(network, mask))
			return false;

		return true;
	}
	function TEMP_RulesCount(path, id)
	{
		var max = parseInt(XG(path+"/max"), 10);
		var cnt = parseInt(XG(path+"/count"), 10);
		var rmd = max - cnt;
		OBJ(id).innerHTML = rmd;
	}
	//==20121127 jack add for sony pop change msg==//
	function ChangePage()
	{
		var dirty = COMM_IsDirty(false);
		if (!dirty && PAGE.IsDirty)
			dirty = PAGE.IsDirty();
		if (dirty)
		{
			return confirm("<?echo I18N("j","Discard Changes?");?>");
		}
		else
			return true;
	}
	//==20121127 jack add for sony pop change msg==//
	
	//==20130321 re-arange height of text==//
	function Initial_textheight()
	{
		//get all class
		var text_l = getElementsByClassName("textinput_l");
		var text_r = getElementsByClassName("textinput_r");
		
		
		//error check
		if(text_l.length <= 0)
			return;
		if(text_l.length != text_r.length)
			return;
		//set auto height
		for(var i=0;i<text_l.length;i++)
			setHeightauto(text_l[i]);
		//compare height
		for(var i=0;i<text_l.length;i++)
			getHeight(text_l[i],text_r[i]);
	}
	
	function setHeightauto(text_left)
	{
		text_left.style.minHeight="25px";
		text_left.style.height="auto";
	}

	function getHeight(text_left,text_right)
	{
		//BODY.ShowAlert("a="+text_left.offsetHeight);
		if (text_left.offsetHeight>text_right.offsetHeight)
		{
			//set the same height
			text_right.style.height=(text_left.offsetHeight-6) + "px";
			//clear position
			//text_right.style.paddingTop = "0px";
			//text_right.style.paddingBottom= "0px";
		}
	}

	function getElementsByClassName(searchClass,node,tag) 
	{
		if(document.getElementsByClassName){
			return  document.getElementsByClassName(searchClass)
		}else{
			node = node || document;
			tag = tag || '*';
			var returnElements = []
			var els =  (tag === "*" && node.all)? node.all : node.getElementsByTagName(tag);
			var i = els.length;
			searchClass = searchClass.replace(/\-/g, "\\-");
			var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
			while(--i >= 0){
				if (pattern.test(els[i].className) ) {
					returnElements.push(els[i]);
				}
			}
			return returnElements;
		}
	}
	//==20130321 re-arange height of text==//

	function Body() {}
	Body.prototype =
	{
		ShowLogin: function()
		{
			OBJ("loginpwd").value	= "";
			OBJ("noGAC").style.display	= "inline";
			OBJ("content").style.display= "none";
			OBJ("mbox").style.display	= "none";
			OBJ("login").style.display	= "block";
			if (OBJ("loginusr").tagName.toLowerCase()=="input")
			{
				OBJ("loginusr").value = "admin";
				OBJ("loginpwd").focus();
			}
			else
			{
				OBJ("loginpwd").focus();
			}
			OBJ("loginusr").readOnly = true;
		},
		ShowContent: function()
		{
			OBJ("login").style.display	= "none";
			OBJ("mbox").style.display	= "none";
			OBJ("content").style.display= "block";
		},
		ShowMessage: function(banner, msgArray)
		{
			var str = '<h1>'+banner+'</h1>';
			for (var i=0; i<msgArray.length; i++)
			{
				str += '<div class="loginbox">';
				str += '<span class="message">'+msgArray[i]+'</span>';
				str += '</div>';				
			}
			OBJ("message").innerHTML = str;
			OBJ("login").style.display	= "none";
			OBJ("content").style.display= "none";
			OBJ("mbox").style.display	= "block";
		},
		rtnURL: null,
		seconds: null,
		timerId: null,
		timerId_rtn: null,
		Message_OP: null,
		banner_OP: null,
		Countdown: function()
		{
			this.seconds--;
			OBJ("timer").innerHTML = this.seconds;
			if (this.seconds < 1)
			{
				clearTimeout(this.timerId);
				if(!this.rtnURL) this.GotResult();
			}
			else
			{
				this.timerId = setTimeout('BODY.Countdown()',1000);
				if(this.rtnURL && this.seconds==30) this.GotResult();
			}
		},
		GotResult: function()
		{
			if (this.rtnURL)	this.ReturnCheck();
			else				this.ShowContent();
		},
		ReturnCheck: function()
		{
			BODY.timerId_rtn = setTimeout('BODY.ReturnCheck()',5000);
			var ajaxObj = GetAjaxObj("ReturnCheck");
			ajaxObj.createRequest();
			ajaxObj.onCallback = function (xml)
			{
				ajaxObj.release();
				if(xml.Get("/status/result")=="OK" || xml.Get("/status/result")=="Authenication fail") 
				{
					clearTimeout(BODY.timerId);
					clearTimeout(BODY.timerId_rtn);
					self.location.href = BODY.rtnURL;
				}
			}
			ajaxObj.setHeader("Content-Type", "application/x-www-form-urlencoded");
			ajaxObj.sendRequest("check_stats.php", "CHECK_NODE=");
		},		
		ShowCountdown: function(banner, msgArray, sec, url)
		{
			this.rtnURL = url;
			this.seconds = sec;
			if(this.rtnURL) this.seconds = this.seconds + 30;
			var str = '<h1>'+banner+'</h1>';
			for (var i=0; i<msgArray.length; i++)
			{
				str += '<div class="loginbox">';
				str += '<span class="message">'+msgArray[i]+'</span>';
				str += '</div>';				
			}
			str += '<div class="loginbox"><span class="message">';
			str += '<br><?echo I18N("j","Time until restart is complete:");?> : ';
			str += '<span id="timer" style="color:red;"></span>';
			str += '&nbsp; <?echo I18N("j","second(s)");?>';	
			str += '</span></div>';			
			OBJ("message").innerHTML	= str;
			OBJ("login").style.display	= "none";
			OBJ("content").style.display= "none";
			OBJ("mbox").style.display	= "block";
			this.Countdown();
		},
		
		//==20121224 jack add for operation condown==//
		//==device will show message without return to main page==//
		Countdown_OP: function()
		{
			this.seconds--;
			OBJ("timer").innerHTML = this.seconds;
			if (this.seconds < 1)
			{
				clearTimeout(this.timerId);
				this.ShowMessage(this.banner_OP, this.Message_OP);
			}
			else
			{
				this.timerId = setTimeout('BODY.Countdown_OP()',1000);
			}
		},
		ShowCountdown_OP: function(banner, msgArray1,msgArray1_len, msgArray2, sec)
		{
			this.seconds = sec;
			this.Message_OP = msgArray2;
			this.banner_OP = banner;
			var str = '<h1>'+banner+'</h1>';
			for (var i=0; i<msgArray1_len; i++)
			{
				str += '<div class="loginbox">';
				str += '<span class="message">'+msgArray1[i]+'</span>';
				str += '</div>';				
			}
			str += '<div class="loginbox"><span class="message">';
			str += '<br><?echo I18N("j","Time until restart is complete:");?> : ';
			str += '<span id="timer" style="color:red;"></span>';
			str += '&nbsp; <?echo I18N("j","second(s)");?>';	
			str += '</span></div>';
			OBJ("message").innerHTML	= str;
			OBJ("login").style.display	= "none";
			OBJ("content").style.display= "none";
			OBJ("mbox").style.display	= "block";
			
			this.Countdown_OP();
		},
		
		//==20121224 jack add for operation condown==//
		ShowAlert: function(msg)
		{
			alert(msg);
		},
		DisableCfgElements: function(type)
		{
			for (var i = 0; i < document.forms.length; i+=1)
		    {
				var frmObj = document.forms[i];
				for (var idx = 0; idx < frmObj.elements.length; idx+=1)
				{
					if (frmObj.elements[idx].getAttribute("usrmode")=="enable") continue;
					frmObj.elements[idx].disabled = type;
				}
			}
		},
		//////////////////////////////////////////////////
		LoginCallback: null,
		//////////////////////////////////////////////////
		LoginSubmit: function()
		{
			var self = this;
			if (OBJ("loginusr").value=="")
			{
				this.ShowAlert("<?echo "Please input the User Name.";?>")
				OBJ("loginusr").focus();
				return false;
			}
			
			AUTH.Login(
				function(xml)
				{
					switch (xml.Get("/report/RESULT"))
					{
					case "SUCCESS":
						if (self.LoginCallback) self.LoginCallback();
						if(PAGE) PAGE.OnLoad();
						self.GetCFG();		
						self.Onleftmenuload();
						if(PAGE) PAGE.OnLoad();						
						self.ShowContent();
						break;
					case "FAIL":
					case "INVALIDUSER":
					case "INVALIDPASSWD":
						var msgArray =
						[
							'<?echo I18N("j","Password is incorrect.");?>',
							'<input id="relogin" type="button" value="<?echo I18N("j","Login Again");?>" onClick="BODY.ShowLogin();" />'
						];
						self.ShowMessage('<?echo I18N("j","Failed to login to the setting screen.");?>', msgArray);
						OBJ("relogin").focus();
						break;
					case "SESSFULL":
						var msgArray =
						[
							'<?echo I18N("j","Cannot login because the setting screen is currently open in multiple locations. Please try again later.");?>',
							'<input id="relogin" type="button" value="<?echo I18N("j","Login Again");?>" onClick="BODY.ShowLogin();" />'
						];
						self.ShowMessage('<?echo I18N("j","Failed to login to the setting screen.");?>', msgArray);
						OBJ("relogin").focus();
						break;
					case "BAD REQUEST":
						self.ShowAlert("Internal error, BAD REQUEST.");
						break;
					}
				},
				OBJ("loginusr").value,
				OBJ("loginpwd").value,
				null
				);
		},
		Login: function(callback)
		{
			if (callback)	this.LoginCallback = callback;
			if (AUTH.AuthorizedGroup >= 0) { AUTH.UpdateTimeout(); return true; }
			return false;
		},
		Logout: function()
		{
			AUTH.Logout(function(){AUTH.TimeoutCallback();});
		},
		//////////////////////////////////////////////////
		GetCFG: function()
		{
			var self = this;
			if (!this.Login(function(){self.GetCFG();})) return;
			if (AUTH.AuthorizedGroup >= 100) this.DisableCfgElements(true);
			if (PAGE&&PAGE.services!=null)
			{
				COMM_GetCFG(
					false,
					PAGE.services,
					function(xml) {
						PAGE.InitValue(xml);
						PAGE.Synchronize();
						COMM_DirtyCheckSetup();
						if (AUTH.AuthorizedGroup >= 100) BODY.DisableCfgElements(true);
						}
					);
			}
			return;
		},
		OnSubmit: function()
		{
			if (PAGE === null) return;
			PAGE.Synchronize();
			var dirty = COMM_IsDirty(false);
			if (!dirty && PAGE.IsDirty) dirty = PAGE.IsDirty();
			if (!dirty)
			{
				var msgArray =
				[
					'<?echo I18N("j","Settings have not changed. Return to the previous page.");?>',
					'<input id="nochg" type="button" value="<?echo I18N("j","OK");?>" onClick="BODY.ShowContent();" />'
				];
				this.ShowMessage('<?echo I18N("j","Change settings");?>', msgArray);
				OBJ("content").style.display= "none";
				OBJ("mbox").style.display	= "block";
				OBJ("nochg").focus();
				return;
			}

			var xml = PAGE.PreSubmit();
			if (xml === null) return;

			if('<?echo $_GLOBALS["TEMP_MYNAME"];?>' != 'bsc_sms_send')
            {
	            var msgArray =
	            [
	                '<?echo I18N("j","Applying the settings.");?>',
	                '<?echo I18N("j","Please wait...");?>'
	            ];
            }
			
			if(PAGE.ShowSavingMessage) PAGE.ShowSavingMessage();
			else this.ShowMessage('<?echo I18N("j","Change the Settings");?>', msgArray);
			AUTH.UpdateTimeout();

			var self = this;
			PXML.UpdatePostXML(xml);
			PXML.Post(function(code, result){self.SubmitCallback(code,result);});
		},
		SubmitCallback: function(code, result)
		{
			if (PAGE.OnSubmitCallback(code, result)) return;
			this.ShowContent();
			switch (code)
			{
			case "OK":
				this.OnReload();
				break;
			case "BUSY":
				this.ShowAlert("<?echo I18N("j","Someone is configuring the device, please try again later.");?>");
				break;
			case "HEDWIG":
				this.ShowAlert(result.Get("/hedwig/message"));
				if (PAGE.CursorFocus) PAGE.CursorFocus(result.Get("/hedwig/node"));  
				break;
			case "PIGWIDGEON":
				if (result.Get("/pigwidgeon/message")=="no power")
				{
					AUTH.Logout();
					BODY.ShowLogin();
				}
				else
				{
					this.ShowAlert(result.Get("/pigwidgeon/message"));
				}
				break;
			}
		},
		OnReload: function()
		{
			if(PAGE)
			{
				if(PAGE.OnReload) PAGE.OnReload();
				else PAGE.OnLoad();
			}
			this.GetCFG();
		},
		//////////////////////////////////////////////////
		OnLoad: function()
		{
			var self = this;
			OBJ("language").value = "<? echo $lang;?>";
			if (AUTH.AuthorizedGroup < 0)	{ this.ShowLogin(); return; }
			else							this.ShowContent();
			
			AUTH.TimeoutCallback = function()
			{
				var msgArray =
				[
					'<?echo I18N("j","You have successfully logged out.");?>',
					'<input id="tologin" type="button" value="<?echo I18N("j","To login page");?>" onClick="BODY.ShowLogin();" />'
				];
				self.ShowMessage('<?echo I18N("j","Logout");?>', msgArray);
				self.DisableCfgElements(false);
				if (PAGE) PAGE.OnLoad();
				OBJ("tologin").focus();
			};
<? 	
			if ($TEMP_STYLE=="complex" || $TEMP_STYLE=="support")
				$left_menu = 1;
			else
				$left_menu = 0;
?>				
      		var left_menu = <? echo $left_menu;?>;
      		 
	        if(left_menu == 1)
	        {			
				this.Onleftmenuload();
			}
			
			if(PAGE) PAGE.OnLoad();
			this.GetCFG();
		},
		
		Onleftmenuload:function()
		{
			myMenu = new SDMenu("leftmenu");
			myMenu.init();
	
		   var tmpmygroup = "<?echo $_GLOBALS["TEMP_MYGROUP"];?>";
		
		   switch (tmpmygroup)
           {
           	case "main":
				myMenu.expandMenu(myMenu.submenus[0]);
			break;
			case "wireless": 
                myMenu.expandMenu(myMenu.submenus[1]);
            break;
			case "status": 
                myMenu.expandMenu(myMenu.submenus[2]);
            break;
			case "tools": 
                myMenu.expandMenu(myMenu.submenus[3]);
            break;
			default:
				break;
			}		
		},
		
		OnUnload: function() { if (PAGE) PAGE.OnUnload(); OnunloadAJAX(); },
		OnKeydown: function(e)
		{
			switch (COMM_Event2Key(e))
			{
			case 13: this.LoginSubmit();
			default: return;
			}
		},
		InjectTable: function(tblID, uid, data, type)
		{
			var rows = OBJ(tblID).getElementsByTagName("tr");
			var tagTR = null;
			var tagTD = null;
			var i;
			var str;
			var found = false;
			
			/* Search the rule by UID. */
			for (i=0; !found && i<rows.length; i++) if (rows[i].id == uid) found = true;
			if (found)
			{
				for (i=0; i<data.length; i++)
				{
					tagTD = OBJ(uid+"_"+i);
					switch (type[i])
					{
					case "checkbox":
						str = "<input type='checkbox'";
						str += " id="+uid+"_check_"+i;
						if (COMM_ToBOOL(data[i])) str += " checked";
						str += " disabled>";
						tagTD.innerHTML = str;
						break;
					case "text":
						str = data[i];
						if(typeof(tagTD.innerText) !== "undefined")	tagTD.innerText = str;
						else if(typeof(tagTD.textContent) !== "undefined")	tagTD.textContent = str;
						else	tagTD.innerHTML = str;
						break;	
					default:
						str = data[i];
						tagTD.innerHTML = str;
						break;
					}
				}
				return;
			}

			/* Add a new row for this entry */
			tagTR = OBJ(tblID).insertRow(rows.length);
			tagTR.id = uid;
			/* save the rule in the table */
			for (i=0; i<data.length; i++)
			{
				tagTD = tagTR.insertCell(i);
				tagTD.id = uid+"_"+i;
				tagTD.className = "content";
				switch (type[i])
				{
				case "checkbox":
					str = "<input type='checkbox'";
					str += " id="+uid+"_check_"+i;
					if (COMM_ToBOOL(data[i])) str += " checked";
					str += " disabled>";
					tagTD.innerHTML = str;
					break;
				case "text":
					str = data[i];
					if(typeof(tagTD.innerText) !== "undefined")	tagTD.innerText = str;
					else if(typeof(tagTD.textContent) !== "undefined")	tagTD.textContent = str;
					else	tagTD.innerHTML = str;
					break;
				default:
					str = data[i];
					tagTD.innerHTML = str; 
					break;
				}
			}
		},
		CleanTable: function(tblID)
		{
			table = OBJ(tblID);
			var rows = table.getElementsByTagName("tr");
			while (rows.length > 1) table.deleteRow(rows.length - 1);
		},
		OnChangeLanguage: function()
		{
			self.location.href ="./<?echo $_GLOBALS["TEMP_MYNAME"];?>.php?language="+OBJ("language").value;
		}		
	};
	/**************************************************************************/

	var AUTH = new Authenticate(<?=$AUTHORIZED_GROUP?>, <?echo query("/device/session/timeout");?>);
	var PXML = new PostXML();
	var BODY = new Body();
	var PAGE = <? if (isfile("/htdocs/webinc/js/".$TEMP_MYNAME.".php")==1) echo "new Page();"; else echo "null;"; ?>
<?
	/* generate cookie */
	
	if (scut_count($_SERVER["HTTP_COOKIE"], "uid=") == 0)
		echo 'if (navigator.cookieEnabled) document.cookie = "uid="+COMM_RandomStr(10)+"; path=/";\n';
?>	
</script>
</head>

<body class="mainbg" onload="BODY.OnLoad();" onunload="BODY.OnUnload();">
<div class="maincontainer">	
	<div class="headercontainer" style="position:relative;">
		<span>
		<img  src="/pic/top.png" >
		
			<div id="Main_Title" style="position:absolute; margin-top:-50px; margin-left:20px; font-size:24px; font-weight:bold; font-family:Tahoma; color:white;">
				<?echo I18N("h","VAIO Wireless Router");?>
			</div>
			<div id="Second_Title" style="position:absolute; margin-top:-50px; margin-left:calc( 100% - 190px); font-size:24px; font-weight:bold; font-family:Tahoma; color:white">
				<?echo "VGP-WAR100";?>
			</div>
		</span>
	</div>
	<div class="headerfoot">
		<div style="width:99%">
			<?echo I18N("h","Select Language");?>:
				<select id="language" onchange="BODY.OnChangeLanguage();">
					<option value="bg"><?echo I18N("h","Български");?></option>		
					<option value="cs"><?echo I18N("h","Česky");?></option>		
					<option value="de"><?echo I18N("h","Deutsch");?></option>	
					<option value="el"><?echo I18N("h","Ελληνικά");?></option>	
					<option value="en"><?echo I18N("h","English");?></option>	
					<option value="es"><?echo I18N("h","Español");?></option>	
					<option value="fr"><?echo I18N("h","Français");?></option>	
					<option value="hu"><?echo I18N("h","Magyar");?></option>	
					<option value="it"><?echo I18N("h","Italiano");?></option>	
					<option value="jp"><?echo I18N("h","日本語");?></option>	
					<option value="ko"><?echo I18N("h","한국어");?></option>	
					<option value="nl"><?echo I18N("h","Nederlands");?></option>	
					<option value="pl"><?echo I18N("h","Polski");?></option>	
					<option value="pt"><?echo I18N("h","Português");?></option>	
					<option value="ro"><?echo I18N("h","România");?></option>	
					<option value="ru"><?echo I18N("h","Русский");?></option>	
					<option value="sk"><?echo I18N("h","Slovensky");?></option>	
					<option value="th"><?echo I18N("h","ไทย");?></option>	
					<option value="tr"><?echo I18N("h","Türkçe");?></option>	
					<option value="zhcn"><?echo I18N("h","中国語(簡体)");?></option>	
					<option value="zhtw"><?echo I18N("h","中國語(繁體)");?></option>	
				</select>
		</div>
	</div>
<?
if ($TEMP_STYLE=="complex")
{
	echo '  <div id="content" class="leftmenucontainer" style="display:none;">\n'.
         '      <div id="leftmenu" class="leftmenu">\n';
	
	echo '          <div><span class="menuheader">'.I18N("h","Basic").'</span>\n';
    draw_menu($menu_system, $link_system, "|");
	echo '          </div>\n';
	
	echo '          <div><span class="menuheader">'.I18N("h","Wireless LAN").'</span>\n';
    draw_menu($menu_wireless2, $link_wireless2, "|");
    echo '          </div>\n';
	
	echo '          <div><span class="menuheader">'.I18N("h","Status").'</span>\n';
	draw_menu($menu_internet, $link_internet, "|");
	echo '          </div>\n';
	echo '          <div><span class="menuheader">'.I18N("h","Tools").'</span>\n';
	draw_menu($menu_tools, $link_tools, "|");
    echo '          </div>\n'.
	
         '      </div>\n'.
         '      <div class="mainbody">\n'. 
		'<!-- Start of Page Depedent Part. -->\n';
	echo '<!-- '.isfile("/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php").$_GLOBALS["TEMP_MYNAME"].' -->\n';
	if (isfile("/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php")==1)
		dophp("load", "/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php");
	echo '<!-- End of Page Dependent Part. -->\n'.
		 '		</div>\n'.
		 '	</div>';
}
// this simple style is used for wizard.
else if ($TEMP_STYLE=="simple")
{
	echo '	<div id="content" class="simplecontainer" style="display:none;">\n';
	echo '		<div class="simplebody">\n'.
		 '			<div class="blackbox">\n'.	
		 '<!-- Start of Page Depedent Part. -->\n';
	if (isfile("/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php")==1)
		dophp("load", "/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php");
	echo '<!-- End of Page Dependent Part. -->\n'.
		 '			</div>\n'.	
		 '		</div>\n'.
		 '	</div>';	
}
else if ($TEMP_STYLE=="progress")
{
	echo '	<div id="content" >\n'.
		 '		<div class="simplebody">\n'.
		 '<!-- Start of Page Depedent Part. -->\n';
	if (isfile("/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php")==1)
		dophp("load", "/htdocs/webinc/body/".$_GLOBALS["TEMP_MYNAME"].".php");
	echo '<!-- End of Page Dependent Part. -->\n'.
		 '		</div>\n'.
		 '	</div>';
}
?>
	<!-- Start of Login Body -->
	<div id="login" class="simplecontainer" style="display:none;">
		<div class="simplebody">
			<div class="blackbox">
			    <h1><?echo I18N("h","Login");?></h1>
				<div class="loginbox">
					<span class="mode"><?echo I18N("h","Login to the setting screen");?></span>
				</div>			    
				<div class="loginbox">
					<span class="value">
						<input type="text" id="loginusr" onkeydown="BODY.OnKeydown(event);"  />
					</span>
					<span class="delimiter">:</span>
					<span class="name"><?echo I18N("h","User Name");?></span>
				</div>
				<div class="loginbox">
					<span class="value">
						<input type="password" id="loginpwd" maxlength="15" onkeydown="BODY.OnKeydown(event);" />&nbsp;&nbsp;
					</span>
					<span class="delimiter">:</span>
					<span class="name"><?echo I18N("h","Password");?></span>
				</div>			
				<div class="loginbox">
					<span class="loginbutton">
						<input type="button" id="noGAC" value="<?echo I18N("h","Login");?>" onClick="BODY.LoginSubmit();" />
					</span>
				</div>
				<div class="emptyline"></div>
			</div>
			<div class="emptyline"></div>
		</div>
	</div>
	<!-- End of Login Body -->
	<!-- Start of Message Box -->
	<div id="mbox" class="simplecontainer" style="display:none;">
		<div class="simplebody">
			<div class="blackbox">
				<span id="message"></span>
				<div class="emptyline"></div>
			</div>
		</div>
	</div>
	<!-- End of Message Box -->
	<div class="footercontainer">
		<p align="right">Copyright 2013 Sony Corporation</p>
	</div>					
</div>
</body>
</html>
