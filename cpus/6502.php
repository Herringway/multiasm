<?php

if (!isset($game))
	die ('direct execution = bad');
	
if (!isset($offset))
	$offset = 0;
require_once '6502_registers.php';
require_once '6502_opcodes.php';

function get_known_addresses() {
	global $known_addresses,$game;
	if (array_key_exists('dumpold', $_GET)) {
		foreach($known_addresses as $addr => $val) {
			echo strtoupper(dechex($addr)).'|'.$val."\n";
		}
	}
	$known_addresses = array();
	if (!file_exists('games_defines/'.$game.'asm/known_offsets.txt'))
		return $known_addresses;
	$handle = fopen('games_defines/'.$game.'asm/known_offsets.txt', 'r');
	while (($data = fgetcsv($handle, 1000,'|')) !== FALSE)
		$known_addresses[hexdec($data[0])] = $data[1];
	return $known_addresses;
}

$known_addresses = get_known_addresses() + $registers;

function generate_comment($instruction, $val, $oldinstructions) {
	global $commentfile, $known_addresses, $offset;
	$comment = '';
	switch(get_instruction_addressing($instruction)) {
		case 'absolute':
		case 'absolutejmp':
		case 'absoluteindexedx':
		case 'absoluteindexedy':
			if (array_key_exists($val, $known_addresses))
				$comment = '; '.$known_addresses[$val];
			break;
	}
	if ($commentfile)
		$comment = '; '.trim(fgets($commentfile));
	return $comment;
}
function uint($i, $bits) {
	return $i < pow(2,$bits-1) ? $i : 0-(pow(2,$bits)-$i);
}
$handle = fopen($romfile, 'r');
if (!$handle)
	die('Could not open file');
fseek($handle,0x7FFC+16);
$defaultoffset = 0x8000+ord(fgetc($handle)) + (ord(fgetc($handle))<<8);
if (($offset < 0x8000) || ($offset >= 0x8000+filesize($romfile)))
	$offset = $defaultoffset;
fseek($handle, $offset-0x8000+16);
if (array_key_exists($offset, $known_addresses))
	$routinename = ' - '.$known_addresses[$offset];
$commentfile = null;
$commentfilename = sprintf('./ebasm/%06X.txt', $offset);
if (file_exists($commentfilename))
	$commentfile = fopen($commentfilename, 'r');
$argsf = $args = array('  ','  ','  ');
$output = ""; $beginlink = ''; $endlink = ''; $val = 0; $b = 0;
$oldinstructions = array(array('',''),array('',''));
while (!feof($handle)) {
	$oldinstructions[1] = $oldinstructions[0];
	$oldinstructions[0] = array($b, $val);
	$val = 0;
	$b = ord(fgetc($handle));
	$size = $addressing[$instructions[$b][1]][1];
	for ($i = 0; $i < $size; $i++) {
		$args[$i] = ord(fgetc($handle));
		$argsf[$i] = sprintf('%02X', $args[$i]);
		$val += $args[$i]<<($i*8);
	}
	if (array_key_exists(2,$addressing[$instructions[$b][1]])) {
		$beginlink = sprintf('<a href="/disasm/'.$game.'/'.$addressing[$instructions[$b][1]][2].'">', $val,$offset>>16,$offset+uint($val+2,$size*8));
		$endlink = '</a>';
	}
	$output .= sprintf("%06X %02X %2s %2s %2s   %s %s%-11s%s %s\n", $offset, $b, $argsf[0],$argsf[1],$argsf[2],$instructions[$b][0], $beginlink, sprintf($addressing[$instructions[$b][1]][0],$val,$args[0],$args[1],$args[2],$offset>>16,($offset&0xFFFF)+uint($val+2,$size*8)),$endlink, generate_comment($b, $val, $oldinstructions));
	$offset += $size+1;
	$argsf = $args = array('  ','  ','  ');
	$beginlink = '';
	$endlink = '';
	if ($instructions[$b][1] == 'return') break;
}
fclose($handle);
if ($commentfile)
	fclose($commentfile);
$nextoffset = dechex($offset);
?>