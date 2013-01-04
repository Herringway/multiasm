{%extends "base.tpl"%}
{%block content%}
{%for entry in data%}<div class="changelogentry"><img alt="{{entry.authoremail}}" title="{{entry.author}}" style="float: left; margin: 5px;" src="{{entry.authoremail|gravatar('retro',80)}}" /><a name="{{entry.version}}">Commit {{entry.version}}</a>
Date: {{entry.date}}
{%for line in entry.description%}{{line}}
{%endfor%}
</div>
{%endfor%}
{%endblock%}