<?php
class cpu_65816 extends cpucore {
	private $opcodes;
	private $processorFlags;
	private $DBR;
	private $PBR;
	private $farthestbranch;
	private $lastOpcode;
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
		if (!isset($this->opcodes))
			$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
	}
	public function getDefault() {
		$this->dataSource->seekTo(0xFFFC);
		return $this->dataSource->getShort();
	}
	public function getOpcodes() {
		return $this->opcodes;
	}
	private function fix_addr($instruction, $val) {
		if (isset($this->opcodes[$instruction]['addressing']['UseDBR']))
			return ($this->DBR << 16) + $val;
		if (($this->opcodes[$instruction]['addressing']['type'] == 'relative') || ($this->opcodes[$instruction]['addressing']['type'] == 'relativelong'))
			return ($this->currentoffset+uint($val+2,8 * $this->opcodes[$instruction]['addressing']['size']))&0xFFFF;
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
		$tmpoutput = array();
		$this->lastOpcode = $tmpoutput['opcode'] = $this->dataSource->getByte();
		$tmpoutput['args'] = array();
		
		if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'index')
			$size = !$this->processorFlags['8 Bit Index']+1;
		else if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'accum')
			$size = !$this->processorFlags['8 Bit Accum']+1;
		else
			$size = $this->opcodes[$tmpoutput['opcode']]['addressing']['size'];
		$tmpoutput['value'] = 0;
		//debugvar($size, 'opcode size');
		for($j = 0; $j < $size; $j++) {
			$t = $this->dataSource->getByte();
			$tmpoutput['args'][] = $t;
			$tmpoutput['value'] += $t<<($j*8);
		}
		if (($tmpoutput['opcode'] == 0xC2) | ($tmpoutput['opcode'] == 0xE2)) {
			if ($tmpoutput['args'][0]&0x10)
				$this->processorFlags['8 Bit Index'] = ($tmpoutput['opcode'] != 0xC2);
			if ($tmpoutput['args'][0]&0x20)
				$this->processorFlags['8 Bit Accum'] = ($tmpoutput['opcode'] != 0xC2);
		}
		//if (isset($this->opcodes[$tmpoutput['opcode']]['undefined']))
		//	throw new Exception("Undefined opcode encountered.");
			
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