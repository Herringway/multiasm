{%for instruction in data.0%}{%if instruction.label%}{{instruction.label}}:
{%else%}
{%if options.clean is not defined%}{{addrformat|format(instruction.offset)}}: {{opcodeformat|format(instruction.opcode)}} {%for i in 0..2%}{%if instruction.args[i] is defined%}{{'%02X'|format(instruction.args[i])}} {%else%}   {%endif%}{%endfor%}  {%else%}	{%endif%}{{opcodes[instruction.opcode].Mnemonic}} {%if instruction.name%}{%if opcodes[instruction.opcode].printformat is defined %}{{opcodes[instruction.opcode].printformat|format(instruction.name)}}{%else%}{{instruction.name}}{%endif%}{%else%}{%if instruction.value%}{%if opcodes[instruction.opcode].printformat is defined %}{{opcodes[instruction.opcode].printformat|format(instruction.value)}}{%else%}{{instruction.value}}{%endif%}{%endif%}{%endif%}

{%endif%}
{%endfor%}