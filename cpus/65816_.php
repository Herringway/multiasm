<?php
if (!isset($game))
	die ('someone dun goofed (game undefined)');

function get_processor_bits($arg) {
	$output = '';
	$processor_bits = array('Carry', 'Zero', 'IRQ', 'Decimal', '8bit Index', '8bit Accum', 'Overflow', 'Negative');
	for ($i = 0; $i < 8; $i++)
		$output .= $arg&pow(2,$i) ? $processor_bits[$i].' ' : '';

	return $output;
}
function generate_labels($instruction, $val, $offset) {
	global $known_addresses,$instructions, $baseoffset;
	$comment = array();
	switch($instructions[$instruction]['addressing']['type']) {
		case 'procconst':
			$comment = array('description' => sprintf('%s %s', ($instruction == 0xE2 ? 'Set' : 'Unset'), get_processor_bits($val))); break;
		case 'absolute':
			if (array_key_exists((0x7E0000+$val), $known_addresses)) {
				$comment = $known_addresses[(0x7E0000+$val)]; break;
			}
		case 'absolutelong':
		case 'absolutelongjmp':
		case 'absolutelongindexed':
		case 'absolutelongindexedx':
			if (isset($known_addresses[$val]))
				$comment = $known_addresses[$val];
			break;
		case 'absolutejmp':
			if (isset($known_addresses[$baseoffset]['labels']) && isset($known_addresses[$baseoffset]['labels'][sprintf('%04X',$val)])) {
				$v = $known_addresses[$baseoffset]['labels'][sprintf('%04X',$val)];
				$comment = array('name' => is_array($v) ? $v['name'] : $v, 'anchor' => true);
			} if (array_key_exists((($offset&0xFF0000)+$val), $known_addresses))
				$comment = $known_addresses[(($offset&0xFF0000)+$val)];
			break;
		case 'absoluteindexedx':
		case 'absoluteindexedy':
			if (array_key_exists((0x7E0000+$val), $known_addresses))
				$comment = $known_addresses[(0x7E0000+$val)];
			break;
		case 'relative':
			if (isset($known_addresses[$baseoffset]['labels']) && isset($known_addresses[$baseoffset]['labels'][sprintf('%04X',($offset+uint($val+2,8))&0xFFFF)])) {
				$v = $known_addresses[$baseoffset]['labels'][sprintf('%04X',($offset+uint($val+2,8))&0xFFFF)];
				$comment = array('name' => is_array($v) ? $v['name'] : $v, 'anchor' => true);
			}
			break;
	}
	return $comment;
}
function disassemble(&$offset, $accum, $index) {
	global $handle, $instructions, $addressing, $known_addresses, $rellabels,$offsetname, $game;
	$baseoffset = $offset;
	$routinesize = 0xFFFF;
	if (isset($known_addresses[$baseoffset]['size']))
		$routinesize = $known_addresses[$baseoffset]['size'];
	$farthestbranch = $offset;
	fseek($handle, $offset-$game['rombase']);
	$val = 0; $opcode = 0;
	$instructionlist = array();

	while (true) {
		$args = array('','','');
		$uri = '';
		$tool = '';
		$val = 0;
		$opcode = ord(fgetc($handle));
		$instruction = $instructions[$opcode];

		if (isset($known_addresses[$baseoffset]['labels']) && isset($known_addresses[$baseoffset]['labels'][sprintf('%04X',$offset&0xFFFF)])) {
			if (is_array($known_addresses[$baseoffset]['labels'][sprintf('%04X',$offset&0xFFFF)])) {
				unset($v);
				$v = $known_addresses[$baseoffset]['labels'][sprintf('%04X',$offset&0xFFFF)];
				$instructionlist[] = array('label' => $v['name']);
				if (isset($v['accum']))
					$accum = $v['accum'];
				if (isset($v['index']))
					$index = $v['index'];
			} else
				$instructionlist[] = array('label' => $known_addresses[$baseoffset]['labels'][sprintf('%04X',$offset&0xFFFF)]);
		}

		if ($instruction['addressing']['size'] === 'index')
			$size = $index/8;
		else if ($instruction['addressing']['size'] === 'accum')
			$size = $accum/8;
		else
			$size = $instruction['addressing']['size'];


		for ($i = 0; $i < $size; $i++) {
			$args[$i] = ord(fgetc($handle));
			$val += $args[$i]<<($i*8);
		}
		if (($instruction['addressing']['type'] == 'relative')) {
			if (!isset($rellabels[sprintf('%04X', relative_to_absolute($offset, $val, $size)&0xFFFF)]))
				$rellabels[sprintf('%04X', relative_to_absolute($offset, $val, $size)&0xFFFF)] = '';
			if (relative_to_absolute($offset, $val, $size) > $farthestbranch)
				$farthestbranch = relative_to_absolute($offset, $val, $size);
		}
		if (($instruction['addressing']['type'] == 'absolutejmp') && (isset($instruction['addressing']['jump']))) {
			if (($offset&0xFF0000)+$val > $farthestbranch)
				$farthestbranch = ($offset&0xFF0000)+$val;
			if (!isset($rellabels[sprintf('%04X', $val)]))
				$rellabels[sprintf('%04X', $val)] = '';
		}
		if (($opcode == 0xC2) | ($opcode == 0xE2)) {
			if ($args[0]&0x10)
				$index = ($opcode == 0xC2) ? 16 : 8;
			if ($args[0]&0x20)
				$accum = ($opcode == 0xC2) ? 16 : 8;
		}
		$labels = generate_labels($opcode, $val, $offset);
		if (isset($instruction['addressing']['anchorformat']) && isset($labels['anchor'])) {
			if ($instruction['addressing']['type'] == 'relative')
				$v = $known_addresses[$baseoffset]['labels'][sprintf('%04X',relative_to_absolute($offset, $val, $size)&0xFFFF)];
			else if ($instruction['addressing']['type'] == 'absolutejmp')
				$v = $known_addresses[$baseoffset]['labels'][sprintf('%04X',$val)];
			$uri = sprintf($instruction['addressing']['anchorformat'], $offsetname, is_array($v) ? $v['name'] : $v);
		} else if (isset($instruction['addressing']['urlformat']))
			$uri = sprintf($instruction['addressing']['urlformat'], $val,$offset>>16,($offset&0xFF0000)+(($offset+uint($val+$size+1,$size*8))&0xFFFF),(($offset+uint($val+$size+1,$size*8))&0xFFFF), $baseoffset, $offsetname);

		if (isset($labels['type']) && ($labels['type'] == 'table') && ($val > 0xFFFF)) {
			$uri = sprintf('%06X', $val);
		}

		if (($instruction['addressing']['type'] == 'absolutejmp') || ($instruction['addressing']['type'] == 'absolutelongjmp')) {
			unset($v);
			if (isset($known_addresses[$val]['final processor state']))
				$v = $known_addresses[$val]['final processor state'];
			else if (isset($known_addresses[($baseoffset&0xFF0000)+$val]['final processor state']))
				$v = $known_addresses[($baseoffset&0xFF0000)+$val]['final processor state'];
			if (isset($v['accum']))
				$accum = $v['accum'];
			if (isset($v['index']))
				$index = $v['index'];
		}

		$instructionlist[] = array(	'offset' => $offset,
									'opcode' => $opcode,
									'instruction' => $instruction['mnemonic'],
									'arg1' => $args[0],
									'arg2' => $args[1],
									'arg3' => $args[2],
									'comment' => isset($labels['description']) ? $labels['description'] : '',
									'commentarguments' => isset($labels['arguments']) ? $labels['arguments'] : '',
									'name' => isset($labels['name']) ? $labels['name'] : '',
									'value' => sprintf($instruction['addressing']['addrformat'],$val,$args[0],$args[1],$args[2],$offset>>16,($offset+uint($val+$size+1,$size*8))&0xFFFF),
									'printformat' => $instruction['addressing']['printformat'],
									'accum' => $accum,
									'index' => $index,
									'uri' => $uri);
		$offset += $size+1;
		$routinesize -= $size+1;
		if ($routinesize == 0)
			break;
		if (($offset & 0xFF0000) > ($baseoffset & 0xFF0000))
			break;
		if (isset($instruction['addressing']['special']) && ($instruction['addressing']['special'] == 'return')) {
			if ($farthestbranch < $offset)
				break;
		}
	}
	return $instructionlist;
}
if (!isset($offset) || ($offset >= $game['rombase']+filesize($rompath.$game['rom'])) || ($offset < $game['rombase'])) {
	fseek($handle,0xFFFC); //8-bit RESET vector
	$offset = $game['rombase'] + ord(fgetc($handle)) + (ord(fgetc($handle))<<8);
}
$baseoffset = $offset;

if (isset($known_addresses[$offset]['accumsize']))
	$accum = $known_addresses[$offset]['accumsize'];
else
	$accum = '16';
if (isset($known_addresses[$offset]['indexsize']))
	$index = $known_addresses[$offset]['indexsize'];
else
	$index = '16';
for ($i = 2; array_key_exists($i, $argc); $i++) {
	$tmparg = explode('=', $argc[$i]);
	if (count($tmparg) != 2)
		break;
	switch($tmparg[0]) {
		case 'accum':
			$accum = $tmparg[1]; break;
		case 'index':
			$index = $tmparg[1]; break;
	}
	$options[$tmparg[0]] = $tmparg[1];
}

$instructions = yaml_parse_file('cpus/65816_opcodes.yml');

$nextoffset = $offset;
$rellabels = array();
if (isset($known_addresses[$offset]['labels']))
	$rellabels = $known_addresses[$offset]['labels'];
$instructionlist = disassemble($nextoffset, $accum, $index);

?>
