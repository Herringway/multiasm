<?php
class core extends core_base {
	private $opts;
	private $platform;
	private $handle;
	private $opcodes;
	private $addrs;
	const opcodeformat = '%08X';
	function __construct(&$main) {
		$this->opcodes = yaml_parse_file('./cpus/ARM_opcodes.yml');
		$this->handle = $main->gamehandle;
		$this->platform = $main->platform;
		$this->addrs = $main->addresses;
		$this->opts = $main->opts;
		if (!isset($opts['THUMB']))
			$this->opts['THUMB'] = 0;
	}
	public function getDefault() {
		return 0x8000000;
	}
	public function execute($offset,$offsetname) {
		$this->initialoffset = $this->currentoffset = $offset;
		$realoffset = $this->platform->map_rom($offset);
		fseek($this->handle, $realoffset);
		for ($i = 0; $i < 10; $i++) {
			$instruction = array('offset' => $offset, 'THUMB' => $this->opts['THUMB']);
			if (!$this->opts['THUMB']) {
				$b = ord(fgetc($this->handle)) + (ord(fgetc($this->handle))<<8) + (ord(fgetc($this->handle))<<16) + (ord(fgetc($this->handle))<<24);
				$inst = sprintf('%028b', $b&0xFFFFFFF);
				$instruction['conditional'] = $this->opcodes['conditionals'][$b>>28];
			} else {
				$b = (ord(fgetc($this->handle))<<8) + ord(fgetc($this->handle));
				$inst = sprintf('%016b', $b);
				$instruction['conditional'] = '';
			}
			$instruction['opcode'] = $b;
			$instruction['instruction'] = $inst;
			$offset += 2 + (2-$this->opts['THUMB']*2);
			$output[] = $instruction;
		}
		return $output;
	}

}
?>