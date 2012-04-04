{extends "base.tpl"}
{block "assembly"}
	{for i 0 15}{string_format($i+$thisoffset%16, '%02X')} {/for}
<hr>{string_format($thisoffset, '%06X')}: {loop $data.1}{string_format($, '%02X')} {if ($.loop.default.index % 16) == 15} 
{string_format($_.thisoffset+$.loop.default.index+1, '%06X')}: {/if}{/loop}
{/block}