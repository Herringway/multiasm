{extends "base.tpl"}
{block "assembly"}{loop $instructions}{if !$label}
{string_format($offset, $_root.addrformat)}: {string_format($opcode, '%02X')} {for i 0 2}{if $args.$i !== null}{string_format($args.$i, '%02X')} {else}   {/if}{/for}  {$instruction} {if $uri || $comment}<a{/if}{if $comment} title="{$comment}{loop $commentarguments}

{$_key}:{$}{/loop}"{/if}{if $uri} href="/{$_.game}/{$uri}"{/if}{if $uri || $comment}>{/if}{if $name}{string_format($name, $printformat)}{else}{string_format($value, $printformat)}{/if}{if $uri || $comment}</a>{/if}{else}<a name="{$label}">{$label}</a>:{/if}
{/loop}{/block}