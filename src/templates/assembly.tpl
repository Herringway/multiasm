{%extends "base.tpl"%}
{%block content%}{%for instruction in data.0%}{%if instruction.label%}<a class="label" href="#{{instruction.label}}" name="{{instruction.label}}">{{instruction.label}}</a>:
{%else%}
{%if options.clean is not defined%}<span class="address">{{addrformat|format(instruction.offset)}}:</span> <span class="opcode">{{opcodeformat|format(instruction.opcode)}}</span> <span class="args">{%for i in 0..2%}{%if instruction.args[i] is defined%}{{'%02X'|format(instruction.args[i])}} {%else%}   {%endif%}{%endfor%}</span>  {%else%}	{%endif%}<span class="instruction">{{opcodes[instruction.opcode].Mnemonic}}</span> <span class="interpargs"{%if instruction.comments%} title="{%for key,comment in instruction.comments%}{%if key != 'description'%}{{key}}: {%endif%}{%if comment|keys|length < 1%}{{comment}}{%else%}

{%for subkey,subcomment in comment%}{%for i in range(0,key|length)%} {%endfor%}{{subkey}}: {{subcomment}}
{%endfor%}{%endif%}

{%endfor%}
"{%endif%}>{%if instruction.uri%}<a href="{{rootdir}}{{coremod}}/{{instruction.uri}}">{%endif%}{%if instruction.name%}{%if opcodes[instruction.opcode].printformat is defined %}{{opcodes[instruction.opcode].printformat|format(instruction.name)}}{%else%}{{instruction.name}}{%endif%}{%else%}{%if instruction.value%}{%if opcodes[instruction.opcode].printformat is defined %}{{opcodes[instruction.opcode].printformat|format(instruction.value)}}{%else%}{{instruction.value}}{%endif%}{%endif%}{%endif%}{%if instruction.uri%}</a>{%endif%}</span>
{%endif%}
{%endfor%}{%endblock%}