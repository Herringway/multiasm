{%extends "base.tpl"%}
{%block header%}
		<!--[if lt IE 9]><script language="javascript" type="text/javascript" src="/js/excanvas.min.js"></script><![endif]-->
		<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
		<script src="http://code.jquery.com/jquery-migrate-1.1.0.min.js"></script>
		<script type="text/javascript" src="/js/jquery.jqplot.min.js"></script>
		<script type="text/javascript" src="/js/plugins/jqplot.cursor.min.js"></script>
		<script type="text/javascript" src="/js/plugins/jqplot.pointLabels.min.js"></script>
		<script type="text/javascript" src="/js/plugins/jqplot.highlighter.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/js/jquery.jqplot.min.css" />
		<script type="text/javascript">
			$(document).ready(function(){
{%if data[0][0] is not iterable%}
			  var plot1 = $.jqplot ('chart', [[{%for entry in data%}{{entry[0]}}{%if not loop.last%},{%endif%}{%endfor%}]], {
{%else%}
			  var plot1 = $.jqplot ('chart', [{%for list in data%}[{%for entry in list[0]%}{{entry[0]}}{%if not loop.last%},{%endif%}{%endfor%}]{%if not loop.last%},{%endif%}{%endfor%}], {
{%endif%}
					seriesDefaults: {
						markerOptions: {
							size: 6
						}
					},
					highlighter: {
						show: true
					},
					cursor: {
						show: true,
						showTooltip: false,
						zoom: true
					},
					legend: {
						show: true,
						location: 'se'
					}
				});
			});
		</script>
{%endblock%}
{%block menu%}{%endblock%}
{%block content%}
<div id="chart" style="height:400px;width:100%;"></div>
{%endblock%}