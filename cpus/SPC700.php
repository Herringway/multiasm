<?php
class core extends core_base {
	private $opcodes;
	
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/SPC700_opcodes.yml');
	}
	public function getDefault() {
		return platform::get()->map_rom(0x200);
	}
	public function fixBranch($val) {
		return $this->currentoffset + uint($val[0]+2,8);
	}
	public function execute($offset) {
		rom::get()->seekTo(platform::get()->map_rom($offset));
		$output = array();
		$this->initialoffset = $this->currentoffset = $offset;
		while (($opcode = rom::get()->getByte()) !== null) {
			$val = 0;
			$tmp = array('opcode' => $opcode, 'instruction' => isset($this->opcodes[$opcode]['instruction']) ? $this->opcodes[$opcode]['instruction'] : dechex($opcode), 'offset' => $this->currentoffset, 'args' => array());
			$size = isset($this->opcodes[$opcode]['size']) ? $this->opcodes[$opcode]['size'] : 1;
			
			for ($i = 1; $i < $size; $i++)
				$val += ($tmp['args'][] = rom::get()->getByte())<<(($i-1)*8);
				
			if ((isset($this->opcodes[$opcode]['branch']) && !isset($this->branches[$this->fixBranch($tmp['args'])])) && ($this->fixBranch($tmp['args']) >= $this->initialoffset)) {
				$tmp['uri'] = sprintf('%04X', $this->initialoffset).'#'.sprintf('%04X', $this->fixBranch($tmp['args']));
				$this->branches[$this->fixBranch($tmp['args'])] = '';
			} else
				$tmp['uri'] = isset($this->opcodes[$opcode]['jump']) ? vsprintf($this->opcodes[$opcode]['addrformat'], array($val) + $tmp['args']) : '';
			$this->currentoffset += $size;
			$tmp['value'] = vsprintf($this->opcodes[$opcode]['addrformat'], array_merge(array($val),$tmp['args']));
			$tmp['printformat'] = isset($this->opcodes[$opcode]['printformat']) ? $this->opcodes[$opcode]['printformat'] : '%s';
			$output[] = $tmp;
			if (($opcode == 0x6F) || ($opcode == 0x7F) || ($this->currentoffset > 0xFFFF))
				break;
		}
		$i = 0;
		ksort($this->branches);
		
		return $output;
	}
}

?>