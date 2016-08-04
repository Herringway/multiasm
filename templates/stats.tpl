{%extends "base.tpl"%}
{%block header%}
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load("visualization", "1", { packages:["corechart"]});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Data Type');
				data.addColumn('number', 'Bytes');
				data.addRows({{data[0].Size|length}});
{%for key,data in data[0].Size%}
				data.setValue({{loop.index0}}, 0, '{{key}}');
				data.setValue({{loop.index0}}, 1, {{data}});
{%endfor%}
				var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
				chart.draw(data, { width: 450, height: 300, title: 'Data'});
			}
		</script>{%endblock%}
{%block content%}
Known Data: {{'%06X'|format(data[0].Known_Data)}}
Biggest Data: <a href="{{rootdir}}/{{coremod}}/{{data[0].Biggest.name}}">{{data[0].Biggest.name}}</a> - 0x{{'%06X'|format(data[0].Biggest.size)}} bytes
Biggest Routine: <a href="{{rootdir}}/{{coremod}}/{{data[0].Biggest_Routine.name}}">{{data[0].Biggest_Routine.name}}</a> - 0x{{'%04X'|format(data[0].Biggest_Routine.size)}} bytes
<div id="chart_div" style="text-align: center"></div>{%if data[0].miscdata is not empty%}Misc data:
{%for key,datum in data[0].miscdata%}
	{{key}}: {{datum}}<br />{%endfor%}{%endif%}{%endblock%}
