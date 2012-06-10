{extends "base.tpl"}
{block "assembly"}
{loop $data}<a name="{$version}">Commit {$version}</a>
Date: {$date}
{loop $description}	{$}
{/loop}
{/loop}
{/block}