<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}MPASM{/block}{if $routinename} - {$routinename}{/if}</title>
		<link rel="stylesheet" type="text/css" href="/asm.css"/>
		{block "header"}{/block}
	</head>
<body>
	{if !empty($gamelist)}<div class="right">{if $nextoffset}<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a>{/if}
	<select onchange="top.location.href = '/' + this.options[this.selectedIndex].value">{loop $gamelist}<option value="{$_key}"{if $_.title == $} selected="yes"{/if}>{$}</option>{/loop}</select>
	<form action="/index.php"><input type="hidden" name="game" value="{$game}">
	<label>Offset:      <input type="text" value="{$offsetname}" name="begin"></label><br />{block "options"}{/block}
	<input type="submit" value="Submit">
	</form></div>{/if}
	<div class="menu"><div>
	{block "menu"}{loop $menuitems}<a href="#{$_key}">{$}</a><br />{/loop}{/block}
	</div></div>
	<span class="top" title="{loop $arguments}
	{$_key}:{$} 
	{/loop}">{$title}{if $routinename} - {$routinename}{/if}</span>
	<pre>
{block "assembly"}{/block}	</pre>
	<small><a href="/{$game}/stats">Stats</a> <a href="/{$game}/issues">Issues</a> <a href="/{$game}/rommap">Known Addresses</a></small>
</body>
</html>