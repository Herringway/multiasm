{extends "base.tpl"}
{block "assembly"}{loop $data}<a href="/{$_.game}/{string_format($_key, '%s')}">{string_format($_key, '%s')}</a><br />	{loop $}{$}{if !$.loop.default.last}, {/if}{/loop}<hr />{/loop}{/block}
