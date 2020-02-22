<script type="text/javascript">
function Page() {}
Page.prototype =
{

	services: "DEVICE.ACCOUNT,SHAREPORT",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			BODY.OnReload();
			break;
		case "BUSY":
			BODY.ShowAlert("<?echo I18N("j","Someone is configuring the device, please try again later.");?>");
			break;
		case "HEDWIG":
			BODY.ShowAlert(result.Get("/hedwig/message"));
			break;
		case "PIGWIDGEON":
			if (result.Get("/pigwidgeon/message")=="no power")
			{
				AUTH.Logout();
				BODY.ShowLogin();
			}
			else
			{
				BODY.ShowAlert(result.Get("/pigwidgeon/message"));
			}
			break;
		}
		return true;
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		PXML.CheckModule("SHAREPORT", "ignore",null, "ignore"); 
		if (!this.Initial()) return false;
		return true;
	},
	PreSubmit: function()
	{
		if (!this.SaveXML()) return null;
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	admin: null,
	actp: null,
	captcha: null,
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	Initial: function()
	{
		this.actp = PXML.FindModule("DEVICE.ACCOUNT");
		if (!this.actp)
		{
			BODY.ShowAlert("Initial() ERROR!!!");
			return false;
		}
		this.captcha = this.actp + "/device/session/captcha";
		this.actp += "/device/account";
		this.admin = OBJ("admin_p1").value = OBJ("admin_p2").value = XG(this.actp+"/entry:1/password");

		return true;
	},
	SaveXML: function()
	{
		/* The IE browser would treat the text with all spaces as empty according as 
			it would ignore the text node with all spaces in XML DOM tree for IE6, 7, 8, 9.*/		
		if(COMM_IsAllSpace(OBJ("admin_p1").value))
		{
			BODY.ShowAlert("<?echo I18N("j","This password is invalid.");?>");
			return false;
		}
		for(var i=0;i < OBJ("admin_p1").value.length;i++)
		{
			if (OBJ("admin_p1").value.charCodeAt(i) > 256) //avoid holomorphic word
			{ 
				BODY.ShowAlert("<?echo I18N("j","This password is invalid.");?>");
				return false;
			}
		}		
		if (!COMM_EqSTRING(OBJ("admin_p1").value, OBJ("admin_p2").value))
		{
			BODY.ShowAlert("<?echo I18N("j","The entered password and re-entered password do not match. Please enter the same password.");?>");
			return false;
		}
		if (!COMM_EqSTRING(OBJ("admin_p1").value, this.admin))
		{
			XS(this.actp+"/entry:1/password", OBJ("admin_p1").value);
		}
		else
		{
			XS(this.captcha, "0");
			BODY.enCaptcha = false;
		}
		return true;
	},
}

</script>
