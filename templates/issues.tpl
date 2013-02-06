{%extends "base.tpl"%}
{%block content%}{%for key,val in data.0%}<a href="/{{coremod}}/{{'%s'|format(key)}}">{{'%s'|format(key)}}</a><br />	{%for kv in val%}{{kv}}{%if not loop.last%}, {%endif%}{%endfor%}<hr />{%endfor%}{%endblock%}
