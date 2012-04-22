{extends "base.tpl"}
{block "assembly"}
{loop $data.0}${string_format($address, '%06X')} - ${string_format(math("$address+$size-1"), '%06X')} ({string_format($size, '%06X')}):<a{if !$name} class="unknown"{/if} title="{$description}">{if $name}{$name}{else}{sprintf('%s_%06X', $type, $address)}{/if}</a>
{/loop}{/block}
