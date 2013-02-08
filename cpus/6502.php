<?php
class cpu_6502 extends cpucore {
	private $farthestbranch;
	public static function addressFormat() {
		return '%04X';
	}
	protected function initializeProcessor() {
		$this->farthestbranch = 0;
		if ($this->opcodes === array()) {
			$this->opcodes = yaml_parse_file('./cpus/6502_opcodes.yml');
			foreach ($this->opcodes as &$entry) {
				$entry = array_merge($entry, $entry['addressing']);
				unset($entry['addressing']);
			}
		}
	}
	public function getDefault() {
		$this->dataSource->seekTo(0xFFFC);
		return $this->dataSource->getShort();
	}
	private function fix_addr($instruction, $val) {
		if (($this->opcodes[$instruction]['addressing']['type'] == 'relative') || ($this->opcodes[$instruction]['addressing']['type'] == 'relativelong'))
			return ($this->currentoffset+uint($val+2,8 * $this->opcodes[$instruction]['addressing']['size']))&0xFFFF;
		return $val;
	}
	protected function setup($addr) { 
		$this->setBreakPoint(($addr&0xFF0000) + 0x10000); //Break at bank boundary
		$this->PBR = $addr>>16;
	}
	protected function fetchInstruction() {
		if (($this->farthestbranch < $this->currentoffset) && isset($this->opcodes[$this->lastOpcode]['addressing']['special']) && ($this->opcodes[$this->lastOpcode]['addressing']['special'] == 'return'))
			throw new Exception ('Return reached');
		$tmpoutput = array();
		$this->lastOpcode = $tmpoutput['opcode'] = $this->dataSource->getByte();
		$tmpoutput['args'] = array();
		
		$size = $this->opcodes[$tmpoutput['opcode']]['addressing']['size'];
		$tmpoutput['value'] = 0;
		for($j = 0; $j < $size; $j++) {
			$t = $this->dataSource->getByte();
			$tmpoutput['args'][] = $t;
			$tmpoutput['value'] += $t<<($j*8);
		}
			
		$fulladdr = $this->fix_addr($tmpoutput['opcode'], $tmpoutput['value']);
		
		if (($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative') && !in_array($fulladdr + ($this->PBR<<16), $this->branches))
			$this->branches[] = $fulladdr + ($this->PBR<<16);
			
		if ($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative')
			$this->farthestbranch = max($this->farthestbranch, $fulladdr + ($this->currentoffset&0xFF0000));
			
		if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat']))
			$tmpoutput['value'] = sprintf($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat'], $tmpoutput['value'],isset($tmpoutput['args'][0]) ? $tmpoutput['args'][0] : 0,isset($tmpoutput['args'][1]) ? $tmpoutput['args'][1] : 0, isset($tmpoutput['args'][2]) ? $tmpoutput['args'][2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($tmpoutput['value']+$size+1,$size*8))&0xFFFF);

		$this->currentoffset += $size+1;
		return $tmpoutput;
	}
}
?>