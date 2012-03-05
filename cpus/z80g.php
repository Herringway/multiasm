<?php
class core extends core_base {
	const addressformat = '%06X';
	private $opcodes;
	function __construct(&$main) {
		$this->opcodes = yaml_parse_file('./cpus/z80g_opcodes.yml');
		if ($this->opcodes === false)
			throw new Exception('Error parsing opcodes!');
		$this->main = $main;
	}
	public function getDefault() {
		fseek($this->main->gamehandle,$this->main->platform->map_rom(0x102));
		return $this->main->platform->map_rom(ord(fgetc($this->main->gamehandle)) + (ord(fgetc($this->main->gamehandle))<<8));
	}
	public function execute($offset) {
		$this->initialoffset = $this->currentoffset = $offset;
		fseek($this->main->gamehandle, $this->main->platform->map_rom($offset));
		while (true) {
			$opcode = ord(fgetc($this->main->gamehandle));
			if ($opcode == 0xCB)
				$opcode = ($opcode<<8)+ord(fgetc($this->main->gamehandle));
			$args = array();
			$val = 0;
			if (isset($this->main->addresses[$this->initialoffset]['labels']) && isset($this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			if (!isset($this->opcodes[$opcode]))
				throw new Exception(sprintf('Undefined opcode: 0x%02X', $opcode));
			for ($i = 0; $i < $this->opcodes[$opcode]['Size']; $i++) {
				$args[$i] = ord(fgetc($this->main->gamehandle));
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
				if (isset($this->main->addresses[$lookup]['name']))
					$tmp['name'] = $this->main->addresses[$lookup]['name'];
					
				if (isset($this->main->addresses[$lookup]['description']))
					$tmp['comment'] = $this->main->addresses[$lookup]['description'];
					
				if (isset($this->main->addresses[$lookup]['arguments']))
					$tmp['commentarguments'] = $this->main->addresses[$lookup]['arguments'];
			}
			if (isset($this->opcodes[$opcode]['branch'])) {
				$val = $this->currentoffset+uint($val, 8)+$this->opcodes[$opcode]['Size']+1;
				$tmp['uri'] = sprintf('%04X#%s', $this->initialoffset, $this->main->addresses[$this->initialoffset]['labels'][$val&0xFFFF]);
				$tmp['name'] = $this->main->addresses[$this->initialoffset]['labels'][$val&0xFFFF];
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