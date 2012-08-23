{%extends "base.tpl"%}
{%block content%}
{%for entry in data.0%}${{addrformat|format(entry.address)}} - ${{addrformat|format(entry.address+entry.size+1)}} ({{addrformat|format(entry.size)}}):<a{%if entry.name%} class="unknown"{%endif%} title="{{entry.description}}" href="{%if entry.name%}{{entry.name}}{%else%}{{addrformat|format(entry.address)}}{%endif%}">{%if entry.name%}{{entry.name}}{%else%}{{entry.type}}_{{addrformat|format(entry.address)}}{%endif%}</a>
{%endfor%}{%endblock%}
