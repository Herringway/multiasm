<?php
if (!isset($game))
	die ('direct execution = bad');
	
if (!isset($offset))
	$offset = 0x100;

$val = 0; $args = array('','');
require_once 'z80g_opcodes.php';
fseek($handle, $offset);

while (true) {
	$opcode = ord(fgetc($handle));
	for ($i = 0; $i < $opcodes[$opcode][0]; $i++) {
		$args[$i] = ord(fgetc($handle));
		$val += $args[$i]<<($i*8);
	}
	$instructionlist[] = array('offset' => $offset, 'opcode' => $opcode, 'instruction' => sprintf($opcodes[$opcode][1], $val), 'arg1' => $args[0], 'arg2' => $args[1], 'interpretedargs' => '', 'uri' => '');
	if (($opcode == 0xC9) || ($opcode == 0xD9))
		break;
	$offset += $opcodes[$opcode][0]+1;
	$args = array('','');
	$val = 0;
}
$nextoffset = dechex($offset+1);
?>