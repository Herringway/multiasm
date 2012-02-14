{extends "base.tpl"}
{block "options"}<input type="hidden" name="game" value="{$game}">
<div class="optiontitle">Offset:      </div><input type="text" value="{$offsetname}" name="begin"><br />
<label>Initial 8-bit Index: <input type="checkbox" name="index" value="true"></label><br />
<label>Initial 8-bit Accum: <input type="checkbox" name="accum" value="true"></label><br />
<label>Simpler Output: <input type="checkbox" name="clean" value="true"></label><br />
<label>YAML Output: <input type="checkbox" name="yaml" value="true"></label><br />
<input type="submit" value="Submit">
{/block}
{block "assembly"}{loop $instructions}{if !$label}
{if !isset($_root.options.clean)}<span class="address">{string_format($offset, '%06X')}:</span> <span class="opcode">{string_format($opcode, '%02X')}</span> <span class="args">{for i 0 2}{if $args.$i !== null}{string_format($args.$i, '%02X')} {else}   {/if}{/for}</span>  {else}	{/if}<span class="instruction">{$instruction}</span> <span class="interpargs"{if $comment} title="{$comment}{loop $commentarguments}

{$_key}:{$}{/loop}"{/if}>{if $uri}<a href="/{$_.game}/{$uri}">{/if}{if $name}{string_format($name, $printformat)}{else}{string_format($value, $printformat)}{/if}{if $uri}</a>{/if}</span>{else}<a class="label" href="#{$label}" name="{$label}">{$label}</a>:{/if}
{/loop}{/block}
