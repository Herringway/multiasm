{extends "base.tpl"}
{block "assembly"}
<form action="/index.php">
<input type="hidden" value="login" name="coremod">
<label>OpenID<input type="text" value="" name="param"></label><br />
<input type="submit" value="Login">
</form>
{/block}