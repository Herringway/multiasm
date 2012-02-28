{extends "base.tpl"}
{block "assembly"}

{with $data}
<div style="text-align: center;">{$message}


{loop $trace}{$class}{$type}{$function}({implode(',', $args)}) (.{str_replace(getcwd(), '', $file)}:{$line})
{/loop}</div>
{/with}
{/block}