{extends "assembly.tpl"}
{block "options"}<input type="hidden" name="game" value="{$game}">
<label>Offset:      <input type="text" value="{$offsetname}" name="begin"></label><br />
<label>Initial 8-bit Index: <input type="checkbox" name="index" value="true"></label><br />
<label>Initial 8-bit Accum: <input type="checkbox" name="accum" value="true"></label><br />
<label>Simpler Output: <input type="checkbox" name="clean" value="true"></label><br />
<label>YAML Output: <input type="checkbox" name="yaml" value="true"></label><br />
<input type="submit" value="Submit">
{/block}
{block "menu"}
{loop $branches}<a href="#{$}">{$}</a><br />{/loop}
{/block}
