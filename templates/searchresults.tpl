{%extends "base.tpl"%}
{%block content%}
{%for name,entry in data%}
<a href="/{{coremod}}/{{name}}">{{name}}</a>:	{%for subentry in entry%}${{addrformat|format(subentry.Offset)}}: {{subentry.Instruction}} {{subentry.Target}}
{%if not loop.last%}		{%endif%}{%endfor%}
{%endfor%}
{%endblock%}