<?php
class core extends core_base {
	private $opcodes;
	public $initialoffset;
	public $currentoffset;
	public $branches;
	private $main;
	private $accum = 16;
	private $index = 16;
	private $addrs;
	public $placeholdernames = false;
	
	function __construct(&$main) {
		$this->opcodes = yaml_parse_file('./cpus/SPC700_opcodes.yml');
		$this->main = $main;
	}
	public function getDefault() {
		return 0x400;
	}
	public function execute($offset,$offsetname) {
		try {
			$realoffset = $this->main->platform->map_rom($offset);
		} catch (Exception $e) {
			die (sprintf('Cannot disassemble %s!', $e->getMessage()));
		}
		fseek($this->main->gamehandle, $realoffset);
		$output = array();
		$this->currentoffset = $offset;
		while (($opcode = ord(fgetc($this->main->gamehandle))) !== null) {
			$val = 0;
			$tmp = array('opcode' => $opcode, 'instruction' => isset($this->opcodes[$opcode]['instruction']) ? $this->opcodes[$opcode]['instruction'] : dechex($opcode), 'offset' => $this->currentoffset);
			$size = isset($this->opcodes[$opcode]['size']) ? $this->opcodes[$opcode]['size'] : 1;
			
			for ($i = 1; $i < $size; $i++)
				$val += ($tmp['args'][] = ord(fgetc($this->main->gamehandle)))<<(($i-1)*8);
				
			$this->currentoffset += $size;
			$tmp['value'] = $val;
			$tmp['printformat'] = isset($this->opcodes[$opcode]['printformat']) ? $this->opcodes[$opcode]['printformat'] : '';
			$output[] = $tmp;
			if (($opcode == 0x6F) || ($opcode == 0x7F))
				break;
		}
		return $output;
	}
}

?>