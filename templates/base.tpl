<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}Disassembler{/block}{if $routinename} - {$routinename}{/if}</title>
		<style type="text/css">
			body	{ text-align: center; }
			a		{ text-decoration: none; }
			.palette { text-shadow: 0 0 0.5em white; width: 100px; height: 100px; text-align: center; display: table-cell; vertical-align:middle;}
			a[title] { text-decoration: underline; }
			form	{ position: absolute; background: white; border: 1px solid black; right: 0px; }
			pre		{ text-align: left; font-family: monospace; color: black; border: 1px dashed black; background-color: lightgray; padding: 2px; margin: 6px; overflow: auto; }
			span	{ position: relative; top: 15px; width: 150px; background:white; border: 2px solid black; }
			.highlight { background: yellow; }
			.rightside { background: white; border: 1px solid black; }
			.optiontitle { float: left; text-align: left; width: 75px; display: inline-block; }
		</style>
		{block "header"}{/block}
	</head>
<body>
<form action="/index.php">
{block "options"}{/block}
</form>
{if $nextoffset}
<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a><br>{/if}
<span title="{loop $arguments}
{$_key}:{$} 
{/loop}">{$title}{if $routinename} - {$routinename}{/if}</span>
<pre>
{block "assembly"}{/block}
</pre>
{if $nextoffset}
<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a><br />{/if}
<small><a href="/{$game}/stats">Stats</a> <a href="/{$game}/issues">Issues</a></small>
</body>
</html>