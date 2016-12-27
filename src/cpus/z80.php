<?php
class cpu_z80 extends cpucore {
	const addressformat = '%06X';
	private $format = 'Zilog';
	protected function initializeProcessor() {
		if ($this->opcodes === array())
			$this->opcodes = $this->filterMnemonics(yaml_parse_file('./src/cpus/z80g_opcodes.yml'));
	}
	public function getDefault() {
		return 0x100;
	}
	public function fetchInstruction() {
		if (($this->lastOpcode == 0xC9) || ($this->lastOpcode == 0xD9))
			throw new Exception('EOF');
		$opcode = $this->dataSource->getByte();
		if ($opcode == 0xCB)
			$opcode = ($opcode<<8)+$this->dataSource->getByte();
		$args = array();
		$val = 0;
		if (!isset($this->opcodes[$opcode]))
			return array('offset' => $this->currentoffset, 'opcode' => $opcode);
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
			$this->branches[] = $val;
			$tmp['target'] = $val;
		}
		if (isset($this->opcodes[$opcode]['Target']))
			$tmp['target'] = $val;
		if (isset($this->opcodes[$opcode]['Address']))
			$tmp['value'] = sprintf($this->opcodes[$opcode]['Address'], $val);
		$this->currentoffset += $this->opcodes[$opcode]['Size']+1;
		return $tmp;
	}
	
	public function getOpcodes() {
		return $this->opcodes;
	}
	private function filterMnemonics($data) {
		foreach ($data as $entry=>&$opcode) {
			if (isset($opcode[$this->format]))
				$opcode = array_merge($opcode, $opcode[$this->format]);
			if (isset($opcode['Intel']))
				unset($opcode['Intel']);
			if (isset($opcode['Zilog']))
				unset($opcode['Zilog']);
		}
		return $data;
	}
}
?>
