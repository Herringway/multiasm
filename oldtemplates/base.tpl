<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}MPASM{/block}{if $description} - {$description}{/if}</title>
		<link rel="stylesheet" type="text/css" href="/mpasm.css"/>
		{block "header"}{/block}
	</head>
<body>
	{if !$hideright}<div class="right"><div class="loginbox">{if $user}<img style="float: right;" src="{gravatar "$user.username@gmail.com"}" />{$user.username}{if $user.admin}<br />Admin{/if}<br /><a href="/logout">Log out</a>{else}<a href="/login">Login</a>{/if}</div>{if !empty($gamelist)}<select onchange="top.location.href = '/' + this.options[this.selectedIndex].value">{loop $gamelist}<option value="{$_key}"{if $_.title == $} selected="yes"{/if}>{$}</option>{/loop}</select>{/if}
	{if $nextoffset}<a rel="next" accesskey="n" href="/{$coremod}/{string_format($nextoffset,'%s')}">Next Function</a>{/if}
	{if $form}{with $form}
	<form action="/index.php"><input type="hidden" name="coremod" value="{$_root.coremod}">
	<label>Offset<input type="text" value="{$_root.offsetname}" name="param"></label><br />{loop $options}{if ($_root.user.admin || !$adminonly)}
	<label>{$label}<input type="{$type}" value="{$value}" name="{$id}"></label><br />{/if}{/loop}
	<input type="submit" value="Submit">
	</form>{/with}{/if}</div>{/if}
	<div class="menu"><div>
	{block "menu"}{loop $menuitems}<a href="#{$_key}">{$}</a><br />{/loop}{/block}
	</div></div>
	<span class="{if $error}error{else}top{/if}" title="{loop $comments}
{$_key}:{$} 
{/loop}">{$title}{if $description} - {$description}{/if}</span>
	<pre{if $error} class="error"{/if}>
{block "assembly"}{/block}	</pre>
	<small>{loop $submods}<a href="/{$_.coremod}/{$_key}">{$}</a> {/loop}</small>
</body>
</html>