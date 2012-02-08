{extends "base.tpl"}
{block "options"}<input type="hidden" name="game" value="{$game}">
<div class="optiontitle">Offset:      </div><input type="text" value="{string_format($thisoffset,'%06X')|upper}" name="begin"><br />
<label>Initial 8-bit Index:       <input type="checkbox" name="index" value="8"></label><br />
<label>Initial 8-bit Accum: <input type="checkbox" name="accum" value="8"></label><br />
<input type="submit" value="Submit">
{/block}
{block "assembly"}{loop $instructions}{if !$label}
{string_format($offset, '%06X')}: {string_format($opcode, '%02X')} {for i 0 2}{if $args.$i !== null}{string_format($args.$i, '%02X')} {else}   {/if}{/for}  {$instruction} {if $uri || $comment}<a{/if}{if $comment} title="{$comment}{loop $commentarguments}

{$_key}:{$}{/loop}"{/if}{if $uri} href="/{$_.game}/{$uri}"{/if}{if $uri || $comment}>{/if}{if $name}{string_format($name, $printformat)}{else}{string_format($value, $printformat)}{/if}{if $uri || $comment}</a>{/if}{else}<a name="{$label}">{$label}</a>:{/if}
{/loop}{/block}
