{extends "base.tpl"}
{block "assembly"}
{if $header}Header:
{loop $header.0}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}<hr />{/if}{loop $a}{string_format($_key, '%X')}:<br/>{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}<hr />{/loop}
{/block}
