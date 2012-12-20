{%extends "base.tpl"%}
{%block content%}
{%for entry in data.0%}${{addrformat|format(entry.address)}} - ${{addrformat|format(entry.address+entry.size+1)}} ({{addrformat|format(entry.size)}}):<a title="{{entry.description}}">{%if entry.name%}{{entry.name}}{%else%}{{entry.type}}_{{addrformat|format(entry.address)}}{%endif%}</a>
{%endfor%}{%endblock%}
