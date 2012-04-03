{extends "assembly.tpl"}
{block "options"}
{if $writemode}
<label>Name<input type="text" value="{$realname}" name="name"></label><br />
<label>Desc<input type="text" value="{$realdesc}" name="desc"></label><br />
<label>Size<input type="text" value="{$size}" name="size"></label><br />
{/if}
<label>Initial 8-bit Index<input type="checkbox" name="index" value="true"></label><br />
<label>Initial 8-bit Accum<input type="checkbox" name="accum" value="true"></label><br />
<label>Simpler Output<input type="checkbox" name="clean" value="true"></label><br />
<label>YAML Output<input type="checkbox" name="yaml" value="true"></label><br />
{if $writemode}
<label>Write to file<input type="checkbox" name="write" value="true"></label><br />
{/if}
{/block}