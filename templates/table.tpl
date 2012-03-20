{extends "base.tpl"}
{block "assembly"}
{loop $data.entries}<a href="#{string_format($_key, '%06X')}" name="{string_format($_key, $_root.addrformat)}">{string_format($.loop.default.index, '%X')} ({string_format($_key, $_root.addrformat)})</a>:
{loop $}  {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{loop $}  
    {$_key}: {if !is_array($)}{if $ === true}true{elseif $ === false}false{else}{$}{/if}{else}{implode(', ', $)}{/if}{/loop}{/if}
{/loop}
{/loop}
{/block}
