<?php
class cpu_65816 extends cpucore {
	private $DBR;
	private $PBR;
	private $farthestbranch;
	public static function addressFormat() {
		return '%06X';
	}
	protected function initializeProcessor() {
		$this->PBR = 0x0000;
		$this->DBR = 0x007E;
		$this->processorFlags['8 Bit Accum'] = true;
		$this->processorFlags['8 Bit Index'] = true;
		$this->processorFlags['Emulation'] = true;
		$this->farthestbranch = 0;
		if ($this->opcodes === array())
			$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
	}
	public function getDefault() {
		$this->dataSource->seekTo(0xFFFC);
		return $this->dataSource->getShort();
	}
	private function fix_addr($instruction, $val) {
		if (isset($this->opcodes[$instruction]['addressing']['UseDBR']))
			return ($this->DBR << 16) + $val;
		if (($this->opcodes[$instruction]['addressing']['type'] == 'relative') || ($this->opcodes[$instruction]['addressing']['type'] == 'relativelong'))
			return ($this->PBR<<16) + (($this->currentoffset+uint($val+2,8 * $this->opcodes[$instruction]['addressing']['size']))&0xFFFF);
		if (isset($this->opcodes[$instruction]['addressing']['UsePBR']))
			return ($this->PBR << 16) + $val;
		return $val;
	}
	protected function setup($addr) { 
		$this->setBreakPoint(($addr&0xFF0000) + 0x10000); //Break at bank boundary
		$this->PBR = $addr>>16;
	}
	protected function fetchInstruction() {
		if (($this->farthestbranch < $this->currentoffset) && isset($this->opcodes[$this->lastOpcode]['addressing']['special']) && ($this->opcodes[$this->lastOpcode]['addressing']['special'] == 'return'))
			throw new Exception ('Return reached');
		$output = array();
		$output['opcode'] = $this->dataSource->getByte();
		$output['args'] = array();
		
		if ($this->opcodes[$output['opcode']]['addressing']['size'] === 'index')
			$size = !$this->processorFlags['8 Bit Index']+1;
		else if ($this->opcodes[$output['opcode']]['addressing']['size'] === 'accum')
			$size = !$this->processorFlags['8 Bit Accum']+1;
		else
			$size = $this->opcodes[$output['opcode']]['addressing']['size'];
		$output['value'] = 0;
		for($j = 0; $j < $size; $j++) {
			$t = $this->dataSource->getByte();
			$output['args'][] = $t;
			$output['value'] += $t<<($j*8);
		}
		if (($output['opcode'] == 0xC2) | ($output['opcode'] == 0xE2)) {
			if ($output['args'][0]&0x10)
				$this->processorFlags['8 Bit Index'] = ($output['opcode'] != 0xC2);
			if ($output['args'][0]&0x20)
				$this->processorFlags['8 Bit Accum'] = ($output['opcode'] != 0xC2);
		}
			
		$fulladdr = $this->fix_addr($output['opcode'], $output['value']);
		
		if (($fulladdr > $this->initialoffset) && (($this->opcodes[$output['opcode']]['addressing']['type'] == 'relative') || ($this->opcodes[$output['opcode']]['addressing']['type'] == 'absolutejmp'))) {
			$this->farthestbranch = max($this->farthestbranch, $fulladdr);
			if (!in_array($fulladdr, $this->branches))
				$this->branches[] = $fulladdr;
		}
		
		if (isset($this->opcodes[$output['opcode']]['addressing']['target']))
			$output['target'] = $fulladdr;
		if (isset($this->opcodes[$output['opcode']]['addressing']['destination']))
			$output['destination'] = $fulladdr;
			
		if (isset($this->opcodes[$output['opcode']]['addressing']['addrformat']))
			$output['value'] = sprintf($this->opcodes[$output['opcode']]['addressing']['addrformat'], $output['value'],isset($output['args'][0]) ? $output['args'][0] : 0,isset($output['args'][1]) ? $output['args'][1] : 0, isset($output['args'][2]) ? $output['args'][2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($output['value']+$size+1,$size*8))&0xFFFF);

		$this->currentoffset += $size+1;
		return $output;
	}
}
?>