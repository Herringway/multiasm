{extends "base.tpl"}
{block "assembly"}
{if $data.header}Header:
{loop $data.header.0}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}<hr />{/if}{loop $data.entries}<a href="#{string_format($_root.data.offsets.$_key, '%06X')}" name="{string_format($_root.data.offsets.$_key, '%06X')}">{string_format($_key, '%X')} ({string_format($_root.data.offsets.$_key, $_root.addrformat)})</a>:<br/>{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}
{/loop}
{/block}
