<?php
class core {
	private $platform;
	public $initialoffset;
	public $currentoffset;
	public $branches;
	private $opcodes;
	function __construct(&$handle,$opts,&$known_addresses) {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		$this->handle = $handle;
		require_once sprintf('platforms/%s.php', $opts['platform']);
		$this->platform = new platform($handle, $opts);
		$this->addrs = $known_addresses;
		$this->opts = $opts;
	}
	public function getDefault() {
		$realoffset = $this->platform->map_rom(0x100);
		return $realoffset;
	}
	public function execute($offset,$offsetname) {
		$this->initialoffset = $this->currentoffset = $offset;
		fseek($this->handle, $this->platform->map_rom($offset));
		while (true) {
			$opcode = ord(fgetc($this->handle));
			$args = array('','');
			$val = 0;
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = ord(fgetc($this->handle));
				$val += $args[$i]<<($i*8);
			}
			$output[] = array('offset' => $offset, 'opcode' => $opcode, 'instruction' => sprintf($this->opcodes[$opcode]['Format'], $val), 'arg1' => $args[0], 'arg2' => $args[1], 'interpretedargs' => '', 'uri' => '');
			if (($opcode == 0xC9) || ($opcode == 0xD9))
				break;
			$offset += $this->opcodes[$opcode]['Size']+1;
		}
		return $output;
	}
}
/*if (!isset($game))
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
$nextoffset = dechex($offset+1);*/
?>