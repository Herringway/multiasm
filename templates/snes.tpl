{extends "assembly.tpl"}
{block "options"}<input type="hidden" name="game" value="{$game}">
<div class="optiontitle">Offset:      </div><input type="text" value="{string_format($thisoffset,'%06X')|upper}" name="begin"><br />
<label>Initial 8-bit Index:       <input type="checkbox" name="index" value="8"></label><br />
<label>Initial 8-bit Accum: <input type="checkbox" name="accum" value="8"></label><br />
<input type="submit" value="Submit">
{/block}
{block "menu"}
{loop $branches}<a href="#{$}">{$}</a><br />{/loop}
{/block}