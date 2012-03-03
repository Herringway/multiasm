<?php
class core extends core_base {
	const addressformat = '%06X';
	private $opcodes;
	function __construct(&$main) {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		$this->main = $main;
	}
	public function getDefault() {
		fseek($this->main->gamehandle,$this->main->platform->map_rom(0x102));
		return $this->main->platform->map_rom(ord(fgetc($this->main->gamehandle)) + (ord(fgetc($this->main->gamehandle))<<8));
	}
	public function execute($offset) {
		$this->initialoffset = $this->currentoffset = $offset;
		fseek($this->main->gamehandle, $this->main->platform->map_rom($offset));
		while (true) {
			$opcode = ord(fgetc($this->main->gamehandle));
			$args = array();
			$val = 0;
			if (!isset($this->opcodes[$opcode]))
				throw new Exception(sprintf('Undefined opcode: 0x%02X', $opcode));
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = ord(fgetc($this->main->gamehandle));
				$val += $args[$i]<<($i*8);
			}
			$output[] =  array(
				'offset' => $offset,
				'opcode' => $opcode,
				'instruction' => $this->opcodes[$opcode]['Instruction'],
				'args' => $args,
				'value' => sprintf($this->opcodes[$opcode]['Address'], $val),
				'printformat' => isset($this->opcodes[$opcode]['PrintFormat']) ? $this->opcodes[$opcode]['PrintFormat'] : '%s',
				'uri' => isset($this->opcodes[$opcode]['Jump']) ? sprintf($this->opcodes[$opcode]['Address'], $val) : '');
				/*
							'comment' => isset($this->main->addresses[$fulladdr]['description']) ? $this->main->addresses[$fulladdr]['description'] : '',
							'commentarguments' => isset($this->main->addresses[$fulladdr]['arguments']) ? $this->main->addresses[$fulladdr]['arguments'] : '',
							'name' => $name,*/
			if (($opcode == 0xC9) || ($opcode == 0xD9))
				break;
			$offset += $this->opcodes[$opcode]['Size']+1;
		}
		return $output;
	}
}
?>