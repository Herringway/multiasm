<?php
class core extends core_base {
	const addressformat = '%04X';
	private $opcodes;
	function __construct(&$main) {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		$this->main = $main;
	}
	public function getDefault() {
		$realoffset = $this->main->platform->map_rom(0x100);
		return $realoffset;
	}
	public function execute($offset,$offsetname) {
		$this->initialoffset = $this->currentoffset = $offset;
		fseek($this->handle, $this->main->platform->map_rom($offset));
		while (true) {
			$opcode = ord(fgetc($this->handle));
			$args = array();
			$val = 0;
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = ord(fgetc($this->handle));
				$val += $args[$i]<<($i*8);
			}
			$output[] = array('offset' => $offset, 'opcode' => $opcode, 'instruction' => sprintf($this->opcodes[$opcode]['Format'], $val), 'args' => $args, 'interpretedargs' => '', 'uri' => '');
			if (($opcode == 0xC9) || ($opcode == 0xD9))
				break;
			$offset += $this->opcodes[$opcode]['Size']+1;
		}
		return $output;
	}
}
?>