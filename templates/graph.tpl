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
			  var plot1 = $.jqplot ('chart1', [[{%for entry in data%}{{entry[0]}}{%if not loop.last%},{%endif%}{%endfor%}]], {
			  highlighter: {
				show: true
			  }
			  });
{%else%}
{%for i in range(1,data|length)%}
			  var plot1 = $.jqplot ('chart{{i}}', [[{%for entry in data[i-1][0]%}{{entry[0]}}{%if not loop.last%},{%endif%}{%endfor%}]], {
			  highlighter: {
				show: true
			  }
			  });
{%endfor%}
{%endif%}
			});
		</script>
{%endblock%}
{%block menu%}{%if data[0][0] is iterable%}{%for i in range(1,data|length)%}<a href="#chart{{i}}">Graph {{i}}</a><br />{%endfor%}{%endif%}{%endblock%}
{%block content%}
{%if data[0][0] is not iterable%}<div id="chart1" style="height:400px;width:100%;"></div>{%else%}
{%for i in range(1,data|length)%}<div id="chart{{i}}" style="height:400px;width:100%;"></div>{%endfor%}{%endif%}
{%endblock%}