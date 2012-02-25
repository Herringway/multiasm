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
				data.addRows({count($data.Size)});
{loop $data.Size}
				data.setValue({$.loop.default.index}, 0, '{$_key}');
				data.setValue({$.loop.default.index}, 1, {$});
{/loop}
				var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
				chart.draw(data, { width: 450, height: 300, title: 'Data'});
			}
		</script>{/block}
{block "assembly"}
Known Data: {string_format($data.Known_Data, '%06X')}
Biggest Data:  <a href="/{$game}/{$data.Biggest.name}">{$data.Biggest.name}</a> - 0x{string_format($data.Biggest.size, '%06X')} bytes
Biggest Routine: <a href="/{$game}/{$data.Biggest_Routine.name}">{$data.Biggest_Routine.name}</a> - 0x{string_format($data.Biggest_Routine.size, '%04X')} bytes
<div id="chart_div" style="text-align: center"></div>{/block}
