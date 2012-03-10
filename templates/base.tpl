<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}MPASM{/block}{if $routinename} - {$routinename}{/if}</title>
		<link rel="stylesheet" type="text/css" href="/asm.css"/>
		{block "header"}{/block}
	</head>
<body>
	{if !$hideright}<div class="right">{if !empty($gamelist)}<select onchange="top.location.href = '/' + this.options[this.selectedIndex].value">{loop $gamelist}<option value="{$_key}"{if $_.title == $} selected="yes"{/if}>{$}</option>{/loop}</select>{/if}
	{if $nextoffset}<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a>{/if}
	<form action="/index.php"><input type="hidden" name="game" value="{$game}">
	<label>Offset<input type="text" value="{$offsetname}" name="begin"></label><br />{block "options"}{/block}
	<input type="submit" value="Submit">
	</form></div>{/if}
	<div class="menu"><div>
	{block "menu"}{loop $menuitems}<a href="#{$_key}">{$}</a><br />{/loop}{/block}
	</div></div>
	<span class="{if $error}error{else}top{/if}" title="{loop $comments}
{$_key}:{$} 
{/loop}">{$title}{if $routinename} - {$routinename}{/if}</span>
	<pre{if $error} class="error"{/if}>
{block "assembly"}{/block}	</pre>
	{if $game}<small><a href="/{$game}/stats">Stats</a> <a href="/{$game}/issues">Issues</a> <a href="/{$game}/rommap">Known Addresses</a></small>{/if}
</body>
</html>