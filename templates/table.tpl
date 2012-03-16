{extends "base.tpl"}
{block "assembly"}
{if $data.header}<a href="#header" name="header">Header</a>:
{loop $data.header}{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}{/loop}<hr />{/if}{loop $data.entries}<a href="#{string_format($_key, '%06X')}" name="{string_format($_key, $_root.addrformat)}">{string_format($.loop.default.index, '%X')} ({string_format($_key, $_root.addrformat)})</a>:<br/>{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}<br />{/loop}
{/loop}
{/block}
