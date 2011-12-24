{extends "base.tpl"}
{block "options"}<input type="hidden" name="game" value="{$game}">
<div class="optiontitle">Offset:      </div><input type="text" value="{string_format($thisoffset,'%08X')|upper}" name="begin"><br />
<label>THUMB:       <input type="checkbox" name="THUMB" value="1"></label><br />
<input type="submit" value="Submit">
{/block}
{block "assembly"}{loop $instructions}{if !$label}
{string_format($offset, '%08X')} {if $THUMB}{string_format($opcode, '%04X')}{else}{string_format($opcode, '%08X')}{/if}  {$instruction} {$conditional} {if $uri || $comment}<a{/if}{if $comment} title="{$comment}{loop $commentarguments}

{$_key}:{$}{/loop}"{/if}{if $uri} href="/{$_.game}/{$uri}"{/if}{if $uri || $comment}>{/if}{if $name}{string_format($name, $printformat)}{else}{string_format($value, $printformat)}{/if}{if $uri || $comment}</a>{/if}{else}<a name="{$label}">{$label}</a>:{/if}
{/loop}{/block}