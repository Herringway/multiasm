<?php
class cpu_SPC700 extends cpucore {
	private $lastOpcode;
	
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/SPC700_opcodes.yml');
	}
	public function getDefault() {
		return 0x500;
	}
	public function fixBranch($val) {
		return $this->currentoffset + uint($val[0]+2,8);
	}
	protected function fetchInstruction() {
		if (($this->lastOpcode == 0x6F) || ($this->lastOpcode == 0x7F) || ($this->currentoffset > 0xFFFF))
			throw new Exception('EOF');
		$val = 0;
		$tmp = array();
		$this->lastOpcode = $tmp['opcode'] = $this->dataSource->getByte();
		$size = isset($this->opcodes[$tmp['opcode']]['size']) ? $this->opcodes[$tmp['opcode']]['size'] : 1;
		
		$tmp['args'] = array();
		for ($i = 1; $i < $size; $i++)
			$val += ($tmp['args'][] = $this->dataSource->getByte())<<(($i-1)*8);
			
		if (isset($this->opcodes[$tmp['opcode']]['branch']) && ($this->fixBranch($tmp['args']) >= $this->initialoffset)) {
			$tmp['target'] = $this->fixBranch($tmp['args']);
			if (!in_array($this->fixBranch($tmp['args']), $this->branches))
				$this->branches[] = $this->fixBranch($tmp['args']);
		} else if (isset($this->opcodes[$tmp['opcode']]['jump']))
			$tmp['target'] = $val;
		$this->currentoffset += $size;
		$tmp['value'] = vsprintf($this->opcodes[$tmp['opcode']]['addrformat'], array_merge(array($val),$tmp['args']));
		$tmp['printformat'] = isset($this->opcodes[$tmp['opcode']]['printformat']) ? $this->opcodes[$tmp['opcode']]['printformat'] : '%s';
		return $tmp;
	}
}

?>