{extends "base.tpl"}
{block "assembly"}
{loop $data}<div style="width: 100%; height: 90px; outline: 1px inset lightgray; vertical-align: middle;"><img alt="{$authoremail}" title="{$author}" style="float: left; margin: 5px;" src="{gravatar $authoremail default=retro}" /><a name="{$version}">Commit {$version}</a>
Date: {$date}
{loop $description}{$}
{/loop}
</div>
{/loop}
{/block}