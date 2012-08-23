{extends "base.tpl"}
{block "assembly"}

{with $data}
<div style="text-align: center;">{$message}


{loop $trace}{if $class}{$class}{$type}{/if}{$function}({implode(',', $args)}) (.{str_replace(getcwd(), '', $file)}:{$line})
{/loop}</div>
{/with}
{/block}