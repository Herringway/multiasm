<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>{block "title"}Disassembler{/block}{if $routinename} - {$routinename}{/if}</title>
		<style type="text/css">
			body	{ text-align: center; max-height: 100%; }
			a		{ text-decoration: none; }
			small a		{ text-decoration: underline; }
			.palette { text-shadow: 0 0 0.5em white; width: 100px; height: 100px; text-align: center; display: table-cell; vertical-align:middle;}
			a[title] { text-decoration: underline; }
			div.right	{ position: fixed; background: white; border: 1px solid black; right: 0px; width: 13%; }
			pre		{  left: 15%; position: absolute; top: 1%; text-align: left; font-family: monospace; color: black; background-color: lightgray; padding: 2px; overflow: auto; width: 70%; max-height: 95%; z-index: 0; border: 1px inset;}
			span.menu	{ position: fixed; left: 0px; top:0px; background: white; font-size: 8pt; text-align: center; width: 15%;  max-height: 100%; height: 100%; overflow: auto; }
			span.top	{ position: relative; top: 0px;  margin-left: auto; margin-right: auto; display: block;  width: 300px; background:white; border: 2px solid black; z-index: 1;}
			.highlight { background: yellow; }
			small	{ position: fixed; bottom: 0px; left: 15%; width: 70%; height: 20px; background: white; text-align: center; }
			.rightside { background: white; border: 1px solid black; }
			.optiontitle { float: left; text-align: left; width: 75px; display: inline-block; }
			.unknown		{ color: darkred; }
			.label			{ color: blue; }
			.opcode			{ color: gray; }
			.args			{ color: black; }
			.address		{ color: green; }
			.instruction 	{ color: gray; }
			.interpargs		{ color: black; }
			.interpargs > a	{ color: blue;}
			/*::-webkit-scrollbar { width: 8px; margin: 0px; }
			::-webkit-scrollbar-thumb { background: rgb(160,160,160); }
			::-webkit-scrollbar-thumb:window-inactive { background: rgb(80,80,80); }*/
		</style>
		{block "header"}{/block}
	</head>
<body>
<div class="right">
<form action="/index.php">
{block "options"}{/block}
</form>
{if $nextoffset}
<a rel="next" accesskey="n" href="/{$game}/{string_format($nextoffset,'%s')}">Next Function</a>{/if}
</div>
<span class="menu">
{block "menu"}{/block}
</span>
<span class="top" title="{loop $arguments}
{$_key}:{$} 
{/loop}">{$title}{if $routinename} - {$routinename}{/if}</span>
<pre>
{block "assembly"}{/block}
</pre>
<small><a href="/{$game}/stats">Stats</a> <a href="/{$game}/issues">Issues</a> <a href="/{$game}/rommap">Known Addresses</a></small>
</body>
</html>