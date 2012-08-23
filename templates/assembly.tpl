{%extends "base.tpl"%}
{%block content%}{%for instruction in data.0%}{%if instruction.label%}<a class="label" href="#{{instruction.label}}" name="{{instruction.label}}">{{instruction.label}}</a>:
{%else%}
{%if options.clean is not defined%}<span class="address">{{addrformat|format(instruction.offset)}}:</span> <span class="opcode">{{opcodeformat|format(instruction.opcode)}}</span> <span class="args">{%for i in 0..2%}{%if instruction.args[i] is defined%}{{'%02X'|format(instruction.args[i])}} {%else%}   {%endif%}{%endfor%}</span>  {%else%}	{%endif%}<span class="instruction">{{instruction.instruction}}</span> <span class="interpargs"{%if instruction.comments%} title="{%for key,comment in instruction.comments%}{{key}}: {{comment}}
{%endfor%}
"{%endif%}>{%if instruction.uri%}<a href="/{{coremod}}/{{instruction.uri}}">{%endif%}{%if instruction.name%}{{printformat|format(instruction.name)}}{%else%}{%if instruction.value%}{{printformat|format(instruction.value)}}{%endif%}{%endif%}{%if instruction.uri%}</a>{%endif%}</span>
{%endif%}
{%endfor%}{%endblock%}