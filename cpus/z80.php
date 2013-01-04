<?php
class cpu_z80 extends cpucore {
	const addressformat = '%06X';
	private $opcodes;
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		if ($this->opcodes === false)
			throw new Exception('Error parsing opcodes!');
	}
	public function getDefault() {
		$this->dataSource->seekTo(0x102);
		return 0x100;
	}
	public function execute($offset) {
		$this->initialoffset = $this->currentoffset = $offset;
		$this->dataSource->seekTo($offset);
		while (true) {
			$opcode = $this->dataSource->getByte();
			if ($opcode == 0xCB)
				$opcode = ($opcode<<8)+$this->dataSource->getByte();
			$args = array();
			$val = 0;
			//if (!isset($this->opcodes[$opcode]))
			//	throw new Exception(sprintf('Undefined opcode: 0x%02X', $opcode));
			else if (!isset($this->opcodes[$opcode])) {
				$output[] = array('offset' => $this->currentoffset, 'opcode' => $opcode, 'instruction' => 'UNKNOWN');
				continue;
			}
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = $this->dataSource->getByte();
				$val += $args[$i]<<($i*8);
			}
			$tmp =  array(
				'offset' => $this->currentoffset,
				'opcode' => $opcode,
				'args' => $args);
			if (isset($this->opcodes[$opcode]['Fixaddr'])) {
				if ($this->opcodes[$opcode]['Fixaddr'] == 4)
					$lookup = $val;
				else if ($this->opcodes[$opcode]['Fixaddr'] == 2)
					$lookup = $val;
			}
			if (isset($this->opcodes[$opcode]['branch'])) {
				$val = $this->currentoffset+uint($val, 8)+$this->opcodes[$opcode]['Size']+1;
				$this->branches[$val] = '';
			}
			if (isset($this->opcodes[$opcode]['Address']))
				$tmp['value'] = sprintf($this->opcodes[$opcode]['Address'], $val);
			$output[] = $tmp;
			if (($opcode == 0xC9) || ($opcode == 0xD9))
				break;
			$this->currentoffset += $this->opcodes[$opcode]['Size']+1;
		}
		return $output;
	}
	
	public function getOpcodes() {
		return $this->opcodes;
	}
}
?>