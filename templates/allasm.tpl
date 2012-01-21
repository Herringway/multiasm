<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>Disassembler</title>
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
<span>Assembly</span>
<pre>
{loop $instructions}{$name}:
{loop $instructions}{if !$label}	{$instruction} {if $name}{string_format($name,$printformat)}{else}{string_format($value,$printformat)}{/if}{else}{$_.name}_{$label}:{/if}
{/loop}
{/loop}
</pre>
</body>
</html>