<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: null,
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result) { return false; },
	OnSubmitCallback: function (code, result) { return true; },
	InitValue: function(xml) { return true; },
	PreSubmit: function() { return null; },
	IsDirty: null,
	Synchronize: function() {}
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
}
</script>
