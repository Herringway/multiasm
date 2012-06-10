<?php
class core extends core_base {
	const addressformat = '%06X';
	private $opcodes;
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		if ($this->opcodes === false)
			throw new Exception('Error parsing opcodes!');
	}
	public function getDefault() {
		return $GLOBALS['platform']->map_rom($GLOBALS['rom']->getShort($GLOBALS['platform']->map_rom(0x102)));
	}
	public function execute($offset) {
		global $rom, $platform, $addresses, $settings;
		$this->initialoffset = $this->currentoffset = $offset;
		$rom->seekTo($platform->map_rom($offset));
		while (true) {
			$opcode = $rom->getByte();
			if ($opcode == 0xCB)
				$opcode = ($opcode<<8)+$rom->getByte();
			$args = array();
			$val = 0;
			if (isset($addresses[$this->initialoffset]['labels']) && isset($addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			if (!$settings['debug'] && !isset($this->opcodes[$opcode]))
				throw new Exception(sprintf('Undefined opcode: 0x%02X', $opcode));
			else if (!isset($this->opcodes[$opcode])) {
				$output[] = array('offset' => $this->currentoffset, 'opcode' => $opcode, 'instruction' => 'UNKNOWN');
				continue;
			}
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = $rom->getByte();
				$val += $args[$i]<<($i*8);
			}
			$tmp =  array(
				'offset' => $this->currentoffset,
				'opcode' => $opcode,
				'instruction' => $this->opcodes[$opcode]['Instruction'],
				'args' => $args,
				'printformat' => isset($this->opcodes[$opcode]['PrintFormat']) ? $this->opcodes[$opcode]['PrintFormat'] : '%s',
				'uri' => isset($this->opcodes[$opcode]['Jump']) ? sprintf('%04X', $val) : '');
			if (isset($this->opcodes[$opcode]['Fixaddr'])) {
				if ($this->opcodes[$opcode]['Fixaddr'] == 4)
					$lookup = $val;
				else if ($this->opcodes[$opcode]['Fixaddr'] == 2)
					$lookup = $val;
				if (isset($addresses[$lookup]['name']))
					$tmp['name'] = $addresses[$lookup]['name'];
					
				if (isset($addresses[$lookup]['description']))
					$tmp['comment'] = $addresses[$lookup]['description'];
					
				if (isset($addresses[$lookup]['arguments']))
					$tmp['commentarguments'] = $addresses[$lookup]['arguments'];
			}
			if (isset($this->opcodes[$opcode]['branch'])) {
				$val = $this->currentoffset+uint($val, 8)+$this->opcodes[$opcode]['Size']+1;
				if (isset($addresses[$this->initialoffset]['labels'][$val&0xFFFF])) {
					$tmp['uri'] = sprintf('%04X#%s', $this->initialoffset, $addresses[$this->initialoffset]['labels'][$val&0xFFFF]);
					$tmp['name'] = $addresses[$this->initialoffset]['labels'][$val&0xFFFF];
				}
				$this->branches[$val] = '';
			}
			if (isset($this->opcodes[$opcode]['Address']))
				$tmp['value'] = sprintf($this->opcodes[$opcode]['Address'], $val);
			$output[] = $tmp;
			if (($opcode == 0xC9) || ($opcode == 0xD9))
				break;
			$this->currentoffset += $this->opcodes[$opcode]['Size']+1;
		}
		return $output;
	}
}
?>