{extends "base.tpl"}
{block "assembly"}{loop $data}<a name="{$_key}">{$_key}:</a>
{loop $}{if !$label}
	<span class="instruction">{$instruction}</span> <span class="interpargs"{if $comment} title="{loop $comments}

{$_key}:{$}{/loop}"{/if}>{if $uri}<a href="/{$__.game}/{$uri}">{/if}{if $name}{string_format($name, $printformat)}{else}{string_format($value, $printformat)}{/if}{if $uri}</a>{/if}</span>{else}<a class="label" href="#{$label}" name="{$label}">{$label}</a>:{/if}
{/loop}

{/loop}{/block}