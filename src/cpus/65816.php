<?php
class cpu_65816 extends cpucore {
	private $farthestbranch;
	public static function addressFormat() {
		return '%06X';
	}
	protected function initializeProcessor() {
		$this->setState('PBR', 0x0000);
		$this->setState('DBR', 0x007E);
		$this->setState('8 Bit Accum', true);
		$this->setState('8 Bit Index', true);
		$this->setState('Emulation', true);
		$this->setState('DP', -1);
		$this->farthestbranch = 0;
		if ($this->opcodes === array()) {
			$this->opcodes = yaml_parse_file('./src/cpus/65816_opcodes.yml');
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
		if (($this->getState('DP') >= 0) && isset($this->opcodes[$instruction]['stack']) && ($this->opcodes[$instruction]['stack'] == 'directpage'))
			return ($this->getState('DP')  << 8 ) + $val;
		if (isset($this->opcodes[$instruction]['UseDBR']))
			return ($this->getState('DBR') << 16) + $val;
		if (($this->opcodes[$instruction]['type'] == 'relative') || ($this->opcodes[$instruction]['type'] == 'relativelong'))
			return ($this->getState('PBR')<<16) + (($this->currentoffset+uint($val+2,8 * $this->opcodes[$instruction]['size']))&0xFFFF);
		if (isset($this->opcodes[$instruction]['UsePBR']))
			return ($this->getState('PBR') << 16) + $val;
		return $val;
	}
	protected function setup($addr) { 
		$this->setBreakPoint(($addr&0xFF0000) + 0x10000, 'bankboundary'); //Break at bank boundary
		$this->setState('PBR', $addr>>16);
	}
	protected function fetchInstruction() {
		if (($this->farthestbranch < $this->currentoffset) && isset($this->opcodes[$this->lastOpcode]['special']) && ($this->opcodes[$this->lastOpcode]['special'] == 'return'))
			throw new Exception ('Return reached');
		$output = array();
		$output['opcode'] = $this->dataSource->getByte();
		$output['args'] = array();
		
		if ($this->opcodes[$output['opcode']]['size'] === 'index')
			$size = !$this->getState('8 Bit Index')+1;
		else if ($this->opcodes[$output['opcode']]['size'] === 'accum')
			$size = !$this->getState('8 Bit Accum')+1;
		else
			$size = $this->opcodes[$output['opcode']]['size'];
		$output['value'] = 0;
		for($j = 0; $j < $size; $j++) {
			$t = $this->dataSource->getByte();
			$output['args'][] = $t;
			$output['value'] += $t<<($j*8);
		}
		if (($output['opcode'] == 0xC2) | ($output['opcode'] == 0xE2)) {
			if ($output['args'][0]&0x10)
				$this->setState('8 Bit Index', ($output['opcode'] != 0xC2));
			if ($output['args'][0]&0x20)
				$this->setState('8 Bit Accum', ($output['opcode'] != 0xC2));
		}
			
		$fulladdr = $this->fix_addr($output['opcode'], $output['value']);
		
		if (($fulladdr > $this->initialoffset) && isset($this->opcodes[$output['opcode']]['jump']) && ($this->opcodes[$output['opcode']]['jump'] === true)) {
			$this->farthestbranch = max($this->farthestbranch, $fulladdr);
			if (!in_array($fulladdr, $this->branches))
				$this->branches[] = $fulladdr;
		}
		
		if (isset($this->opcodes[$output['opcode']]['target']))
			$output['target'] = $fulladdr;
		if (($this->opcodes[$output['opcode']]['type'] == 'directpage') && ($this->getState('DP') >= 0))
			$output['target'] = $fulladdr;
		if (isset($this->opcodes[$output['opcode']]['stack']))
			$output['stack'] = $fulladdr;
		if (isset($this->opcodes[$output['opcode']]['destination']))
			$output['destination'] = $fulladdr;
			
		if (isset($this->opcodes[$output['opcode']]['addrformat']))
			$output['value'] = sprintf($this->opcodes[$output['opcode']]['addrformat'], $output['value'],isset($output['args'][0]) ? $output['args'][0] : 0,isset($output['args'][1]) ? $output['args'][1] : 0, isset($output['args'][2]) ? $output['args'][2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($output['value']+$size+1,$size*8))&0xFFFF);

		$this->currentoffset += $size+1;
		return $output;
	}
}
?>
