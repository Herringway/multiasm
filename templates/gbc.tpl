{extends "base.tpl"}
{block "assembly"}{loop $instructions}
{if !$cleanoutput}{string_format($offset, '%06X')} {string_format($opcode, '%02X')} {if $arg1 !== ''}{string_format($arg1, '%02X')}{else}  {/if} {if $arg2 !== ''}{string_format($arg2, '%02X')}{else}  {/if}   {/if}{$instruction} <a{if $comment} title="{$comment}"{/if}{if $uri} href="/{$_.game}/{$uri}"{/if}>{$interpretedargs}</a>
{/loop}{/block}