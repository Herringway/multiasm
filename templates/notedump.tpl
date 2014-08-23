{%extends "base.tpl"%}
{%block content%}
{%for entry in data%}<a href="/{{coremod}}/{{entry.name}}">${{addrformat|format(entry.address)}} to ${{addrformat|format(entry.address+entry.size-1)}}</a> = {%if entry.description%}{{entry.description|trim()}}{%else%}Unknown{%endif%}{%if entry.notes%}

                     {{entry.notes|replace({"\n":"\n                     "})|trim()}}{%endif%}{%for subentry in entry.Entries%}

	${{addrformat|format(entry.address+subentry.Offset)}} to ${{addrformat|format(entry.address+subentry.Offset+subentry.Size)}}: {{subentry.Description}}{%if subentry.Notes%}

                            {{subentry.Notes|replace({"\n":"\n                     "})|trim()}}{%endif%}{%endfor%}

{%endfor%}{%endblock%}
