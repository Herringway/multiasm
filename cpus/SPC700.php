<?php
class core extends core_base {
	private $opcodes;
	public $initialoffset;
	public $currentoffset;
	public $branches;
	private $handle;
	private $accum = 16;
	private $index = 16;
	private $addrs;
	private $opts;
	public $placeholdernames = false;
	private $platform;
	function __construct(&$handle,$opts,&$known_addresses) {
		$this->opcodes = yaml_parse_file('./cpus/SPC700_opcodes.yml');
		$this->handle = $handle;
		$this->platform = new platform($handle, $opts);
		$this->opts = $opts;
	}
	public function getDefault() {
		return 0x400;
	}
	public function execute($offset,$offsetname) {
		try {
			$realoffset = $this->platform->map_rom($offset);
		} catch (Exception $e) {
			die (sprintf('Cannot disassemble %s!', $e->getMessage()));
		}
		fseek($this->handle, $realoffset);
		$output = array();
		$this->currentoffset = $offset;
		while (($opcode = ord(fgetc($this->handle))) !== null) {
			$val = 0;
			$tmp = array('opcode' => $opcode, 'instruction' => isset($this->opcodes[$opcode]['instruction']) ? $this->opcodes[$opcode]['instruction'] : dechex($opcode), 'offset' => $this->currentoffset);
			$size = isset($this->opcodes[$opcode]['size']) ? $this->opcodes[$opcode]['size'] : 1;
			for ($i = 1; $i < $size; $i++)
				$val += ($tmp['args'][] = ord(fgetc($this->handle)))<<(($i-1)*8);
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