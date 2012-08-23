{extends "base.tpl"}
{block "header"}
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("visualization", "1", { packages:["corechart"]});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Data Type');
				data.addColumn('number', 'Bytes');
				data.addRows({count($data.0.Size)});
{loop $data.0.Size}
				data.setValue({$.loop.default.index}, 0, '{$_key}');
				data.setValue({$.loop.default.index}, 1, {$});
{/loop}
				var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
				chart.draw(data, { width: 450, height: 300, title: 'Data'});
			}
		</script>{/block}
{block "assembly"}
Known Data: {string_format($data.0.Known_Data, '%06X')}
Biggest Data:  <a href="/{$game}/{$data.0.Biggest.name}">{$data.0.Biggest.name}</a> - 0x{string_format($data.0.Biggest.size, '%06X')} bytes
Biggest Routine: <a href="/{$game}/{$data.0.Biggest_Routine.name}">{$data.0.Biggest_Routine.name}</a> - 0x{string_format($data.0.Biggest_Routine.size, '%04X')} bytes
<div id="chart_div" style="text-align: center"></div>{loop $miscdata}
{$_key}: {if is_array($)}{loop $}

{$_key}: {$}{/loop}{else}{$}{/if}<br />{/loop}{/block}
