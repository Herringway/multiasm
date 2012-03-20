<?php
class core extends core_base {
	private $opcodes;
	const opcodeformat = '%08X';
	const addressformat = '%08X';
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/ARM_opcodes.yml');
		$this->main = Main::get();
		if (!isset($this->main->opts['THUMB']))
			$this->main->opts['THUMB'] = 0;
	}
	public function getDefault() {
		$this->main->rom->seekTo(0);
		return 0x8000000+uint($this->main->rom->read_varint(3), 24);
	}
	public function execute($offset) {
		$this->initialoffset = $this->currentoffset = $offset;
		$realoffset = $this->main->platform->map_rom($offset);
		$this->main->rom->seekTo($realoffset);
		for ($i = 0; $i < 10; $i++) {
			$instruction = array('offset' => $offset, 'THUMB' => $this->main->opts['THUMB']);
			if (!$this->main->opts['THUMB']) {
				$b = $this->main->rom->read_varint(4);
				$inst = sprintf('%028b', $b&0xFFFFFFF);
				if (($b&0x0F000000) == 0x0F000000)
					$inst = 'SWI';
				if (($b&0xE1200070) == 0xE1200070)
					$inst = 'BKPT';
				$instruction['conditional'] = $this->opcodes['conditionals'][$b>>28];
			} else {
				$b = $this->main->rom->getShort();
				$inst = sprintf('%016b', $b);
				$instruction['conditional'] = '';
			}
			$instruction['opcode'] = $b;
			$instruction['instruction'] = $inst;
			$offset += 2 + (2-$this->main->opts['THUMB']*2);
			$output[] = $instruction;
		}
		return $output;
	}

}
?>