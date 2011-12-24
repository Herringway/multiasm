<?php
if (!isset($game))
	die ('WHOA BACK OFF');
$tabfile = fopen('cpus/TASM_tabs/'.$tasm_mode.'.tab','r');
$testfile = fopen($romfile, 'r');
$output = ''; $argsf = $args = array('  ','  ','  ');
fseek($testfile, 0x25);
$defaultpc = ord(fgetc($testfile)) + (ord(fgetc($testfile))<<8);

if (!isset($offset))
	$offset = $defaultpc;
fseek($testfile, $offset+0x100);

function read_tabfile($tabfile) {

	while (!feof($tabfile)) {
		$line = fgets($tabfile);
		if ((substr($line, 0, 2) == '/*') || (trim($line) == '') || ($line[0] == '"'))
			continue;
		$tab[] = clean_empty(explode(' ',$line));
	}

	foreach ($tab as $taaaab)
		$opcodes[hexdec($taaaab[2])] = array($taaaab[0],$taaaab[1],$taaaab[3],$taaaab[4]);
		
	ksort($opcodes);
	return $opcodes;
}
function process_args($str,$size,$args,$op) {
	global $game;
	$singlearg = 0;
	$j = 0;
	foreach ($args as $a) {
		if ($a == '') break;
		$singlearg += $a<<(8*($j++));
	}
	if ($str == '""')
		return '';
	if (substr_count($str, '*') == 1) {
		if (($op == 'CALL') || ($op == 'JMP'))
			return sprintf(str_replace('*', '<a href="/disasm/'.$game.'/%0'.(2*$size).'X">$%1$0'.(2*$size).'X</a>', $str), $singlearg);
		return sprintf(str_replace('*', '$%0'.(2*$size).'X', $str), $singlearg);
	}
	if (substr_count($str, '*') > 1)
		return vsprintf(str_replace('*', '$%0'.(2*$size/substr_count($str, '*')).'X', $str), $args);
	return $str;
}

function clean_empty($arr) {
	foreach($arr as $key => &$val) {
		if(trim($val) == '')
			unset($arr[$key]);
		$val = trim($val);
	}
	return array_values($arr);
}
$opcodes = read_tabfile($tabfile);

while (true) {
	$b = ord(fgetc($testfile));
	$args = array('','','');
	if (array_key_exists($b, $opcodes)) {
	if ($opcodes[$b][2] > 1)
		for ($j = 1; $j < $opcodes[$b][2]; $j++) {
			$args[$j-1] = ord(fgetc($testfile));
			$argsf[$j-1] = sprintf('%02X', $args[$j-1]);
		}
	$output .= sprintf('%04X %02X %2s %2s %2s %s %s<br>',$offset, $b, $argsf[0],$argsf[1],$argsf[2], $opcodes[$b][0], process_args($opcodes[$b][1],$opcodes[$b][2]-1,$args,$opcodes[$b][0]));
	$offset += $opcodes[$b][2];
	$argsf = $args = array('  ','  ','  ');
	if (($opcodes[$b][0] == 'RET') || ($opcodes[$b][0] == 'RETI') || feof($testfile))
		break;
	} else {
		$output .= sprintf('%04X %02X          ???<br>',$offset++, $b);
	}
}

?>