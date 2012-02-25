<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}MPASM{/block}{if $routinename} - {$routinename}{/if}</title>
		<link rel="stylesheet" type="text/css" href="/asm.css"/>
		{block "header"}{/block}
	</head>
<body>
	<div class="right">{if $nextoffset}<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a>{/if}{block "options"}{/block}</div>
	<div class="menu"><div>
	{block "menu"}{loop $branches}<a href="#{$}">{$}</a><br />{/loop}{/block}
	</div></div>
	<span class="top" title="{loop $arguments}
	{$_key}:{$} 
	{/loop}">{$title}{if $routinename} - {$routinename}{/if}</span>
	<pre>
{block "assembly"}{/block}	</pre>
	<small><a href="/{$game}/stats">Stats</a> <a href="/{$game}/issues">Issues</a> <a href="/{$game}/rommap">Known Addresses</a></small>
</body>
</html>