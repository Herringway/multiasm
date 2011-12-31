{extends "base.tpl"}
{block "assembly"}
{if $header}Header:
{loop $header.0}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}<hr />{/if}{loop $entries}<a href="#{string_format($_.offsets.$_key, '%06X')}" name="{string_format($_.offsets.$_key, '%06X')}">{string_format($_key, '%X')} ({string_format($_.offsets.$_key, '%06X')})</a>:<br/>{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}
{/loop}
{/block}
