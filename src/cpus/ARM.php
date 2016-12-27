<?php
class cpu_ARM extends cpucore {
	private $THUMB;
	private $counter;
	const opcodeformat = '%08X';
	const addressformat = '%08X';
	public function getDefault() {
		$this->dataSource->seekTo(0x8000000);
		return ($this->dataSource->getLong()&0xFFFFFF) + 0x8000000;
	}
	protected function initializeProcessor() {
		if ($this->opcodes === array())
			$this->opcodes = yaml_parse_file('./src/cpus/ARM_opcodes.yml');
		$this->THUMB = false;
	}
	protected function setup($addr) {
		$this->counter = 10;
	}
	protected function fetchInstruction() {
		if ($this->counter-- == 0)
			throw new Exception('Expired!');
		$instruction = array();
		if (!$this->THUMB) {
			$b = $this->dataSource->getLong();
			$this->currentoffset += 4;
			$inst = sprintf('%028b', $b&0xFFFFFFF);
			if (($b&0x0F000000) == 0x0F000000)
				$inst = 'SWI';
			if (($b&0xE1200070) == 0xE1200070)
				$inst = 'BKPT';
			$instruction['conditional'] = $this->opcodes['conditionals'][$b>>28];
		} else {
			$b = $this->dataSource->getShort();
			$this->currentoffset++;
			$inst = sprintf('%016b', $b);
			$instruction['conditional'] = '';
		}
		$instruction['opcode'] = $b;
		$instruction['instruction'] = $inst;
		$this->currentoffset += 2 + (2-$this->THUMB*2);
		return $instruction;
	}

}
?>
