{extends "base.tpl"}
{block "assembly"}{loop $data.0}{if !$label}
{if !isset($_root.options.clean)}<span class="address">{string_format($offset, $_root.addrformat)}:</span> <span class="opcode">{string_format($opcode, $_root.opcodeformat)}</span> <span class="args">{for i 0 2}{if $args.$i !== null}{string_format($args.$i, '%02X')} {else}   {/if}{/for}</span>  {else}	{/if}<span class="instruction">{$instruction}</span> <span class="interpargs"{if $comments} title="{loop $comments}{$_key}: {$}
{/loop}
"{/if}>{if $uri}<a href="/{$_.coremod}/{$uri}">{/if}{if $name}{string_format($name, defaultv($printformat))}{else}{string_format($value, defaultv($printformat))}{/if}{if $uri}</a>{/if}</span>{else}<a class="label" href="#{$label}" name="{$label}">{$label}</a>:{/if}
{/loop}{/block}