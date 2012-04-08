<?php
class core extends core_base {
	const addressformat = '%06X';
	const template = 'snes';
	private $opcodes;
	private $accum = 16;
	private $index = 16;
	private $DBR;
	private $PBR;
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
		$this->DBR = 0x007E;
	}
	public function getDefault() {
		return rom::get()->getShort(platform::get()->map_rom(0x00FFFC));
	}
	public function getMisc() {
		$output = array();
		if (isset(Main::get()->opts['accum']))
			$output['accumsize'] = 8;
		if (isset(Main::get()->opts['index']))
			$output['indexsize'] = 8;
		return $output;
	}
	private function get_processor_bits($arg) {
		$output = '';
		$processor_bits = array('Carry', 'Zero', 'IRQ', 'Decimal', '8bit Index', '8bit Accum', 'Overflow', 'Negative');
		for ($i = 0; $i < 8; $i++)
			$output .= $arg&pow(2,$i) ? $processor_bits[$i].' ' : '';

		return $output;
	}
	private function fix_addr($instruction, $val) {
		if (isset($this->opcodes[$instruction]['addressing']['UseDBR']))
			return ($this->DBR << 16) + $val;
		if ($this->opcodes[$instruction]['addressing']['type'] == 'relative')
			return ($this->currentoffset+uint($val+2,8))&0xFFFF;
		if ($this->opcodes[$instruction]['addressing']['type'] == 'relativelong')
			return ($this->currentoffset+uint($val+3,16))&0xFFFF;
		if (isset($this->opcodes[$instruction]['addressing']['UsePBR']))
			return ($this->PBR << 16) + $val;
		return $val;
	}
	public function execute($offset) {
		$realoffset = platform::get()->map_rom($offset);
		$this->PBR = $offset>>16;
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset(Main::get()->opts['size']))
			$deflength = Main::get()->opts['size'];
		else if (isset(Main::get()->addresses[$this->initialoffset]['size']))
			$deflength = Main::get()->addresses[$this->initialoffset]['size'];
		if (($realoffset < 0) || ($realoffset > Main::get()->game['size']))
			throw new Exception (sprintf('Bad offset (%X)!', $realoffset));
		rom::get()->seekTo($realoffset);
		$unknownbranches = 0;
		$opcode = 0;
		$index = isset(Main::get()->addresses[$this->initialoffset]['indexsize']) ? Main::get()->addresses[$this->initialoffset]['indexsize'] : $this->index;
		$accum = isset(Main::get()->addresses[$this->initialoffset]['accumsize']) ? Main::get()->addresses[$this->initialoffset]['accumsize'] : $this->accum;
		if (isset(Main::get()->opts['accum']))
			$accum = 8;
		if (isset(Main::get()->opts['index']))
			$index = 8;
		$output = array();
		while (true) {
			$comments = array();
			$tmpoutput = array();
			$tmpoutput['offset'] = $this->currentoffset;
			if (isset($deflength) && ($deflength+$this->initialoffset <= $this->currentoffset))
				break;
			if (($farthestbranch < $this->currentoffset) && !isset($deflength) && isset($this->opcodes[$opcode]['addressing']['special']) && ($this->opcodes[$opcode]['addressing']['special'] == 'return'))
				break;
			if (($this->initialoffset&0xFF0000) != ($this->currentoffset&0xFF0000))
				break;
			if (isset(Main::get()->addresses[$this->initialoffset]['labels']) && isset(Main::get()->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => Main::get()->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$opcode = rom::get()->getByte();
			$args = array();
			
			if ($this->opcodes[$opcode]['addressing']['size'] === 'index')
				$size = $index/8;
			else if ($this->opcodes[$opcode]['addressing']['size'] === 'accum')
				$size = $accum/8;
			else
				$size = $this->opcodes[$opcode]['addressing']['size'];
			$arg = 0;
			for($j = 0; $j < $size; $j++) {
				$t = rom::get()->getByte();
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			if (($opcode == 0xC2) | ($opcode == 0xE2)) {
				$comments['Description'] = ($opcode == 0xC2 ? 'Unset: ' : 'Set: ').$this->get_processor_bits($args[0]);
				if ($args[0]&0x10)
					$index = ($opcode == 0xC2) ? 16 : 8;
				if ($args[0]&0x20)
					$accum = ($opcode == 0xC2) ? 16 : 8;
			}
			if (isset($this->opcodes[$opcode]['undefined']))
				throw new Exception("Undefined opcode encountered. You sure this is assembly?");
				
			$fulladdr = $this->fix_addr($opcode, $arg);
			
			if (($this->opcodes[$opcode]['addressing']['type'] == 'relative') && ($fulladdr + ($this->currentoffset&0xFF0000) > $farthestbranch))
				$farthestbranch = $fulladdr + ($this->currentoffset&0xFF0000);
				
			if ((($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') || ($this->opcodes[$opcode]['addressing']['type'] == 'absolutelongjmp'))) {
				if (isset(Main::get()->addresses[$fulladdr]['name'])) {
					if (platform::get()->isROM($fulladdr))
						$tmpoutput['uri'] = Main::get()->addresses[$fulladdr]['name'];
				} else {
					if (platform::get()->isROM($fulladdr))
						$tmpoutput['uri'] = sprintf('%06X', $fulladdr);
				}
			}
			if ($this->opcodes[$opcode]['addressing']['type'] == 'relativelong')
				$tmpoutput['uri'] = sprintf('%06X', $fulladdr+($this->currentoffset&0xFF0000));
			if (isset(Main::get()->addresses[$fulladdr]['final processor state']['accum']))
				$accum = Main::get()->addresses[$fulladdr]['final processor state']['accum'];
			if (isset(Main::get()->addresses[$fulladdr]['final processor state']['index']))
				$index = Main::get()->addresses[$fulladdr]['final processor state']['index'];
			
			if (isset(Main::get()->addresses[$fulladdr]['name'])) {
				$tmpoutput['name'] = Main::get()->addresses[$fulladdr]['name'];
				if (platform::get()->isROM($fulladdr))
					$tmpoutput['uri'] = $tmpoutput['name'];
			} else if ((($this->opcodes[$opcode]['addressing']['type'] == 'relative') || ($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp')) && isset(Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF])) {
				$tmpoutput['uri'] = sprintf('%s#%s', Main::get()->getOffsetName($this->initialoffset), Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF]);
				$tmpoutput['name'] = ($this->dump ? isset(Main::get()->addresses[$this->initialoffset]['name']) ? Main::get()->addresses[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF];
			} else if (($this->opcodes[$opcode]['addressing']['type'] == 'relative') || (($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') && (isset($this->opcodes[$opcode]['addressing']['jump'])))) {
				if (!isset($this->branches[$fulladdr]) && (count($this->branches) < BRANCH_LIMIT))
					$this->branches[$fulladdr] = '';
			} else if ($this->dump) {
				switch ($this->opcodes[$opcode]['addressing']['type']) {
					case 'absolute': $tmpoutput['name'] = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutejmp': $tmpoutput['name'] = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutelongjmp': $tmpoutput['name'] = sprintf('UNKNOWN_%06X', $arg); break;
					default: $tmpoutput['name'] = ''; break;
				}
			}
			if (isset(Main::get()->game['localvars'])) {
				switch (Main::get()->game['localvars']) {
					case 'directpage':
						if (isset($this->opcodes[$opcode]['addressing']['directpage']) && isset(Main::get()->addresses[$this->initialoffset]['localvars'][$arg]))
							$tmpoutput['name'] = sprintf(Main::get()->settings['localvar format'], Main::get()->addresses[$this->initialoffset]['localvars'][$arg]);
						break;
				}
			}
			$arg = sprintf($this->opcodes[$opcode]['addressing']['addrformat'], $arg,isset($args[0]) ? $args[0] : 0,isset($args[1]) ? $args[1] : 0, isset($args[2]) ? $args[2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($arg+$size+1,$size*8))&0xFFFF);
			if (isset(Main::get()->addresses[$fulladdr]['description']))
				$comments['Description'] = Main::get()->addresses[$fulladdr]['description'];
			if (isset(Main::get()->addresses[$fulladdr]['arguments']))
				$comments = array_merge($comments, Main::get()->addresses[$fulladdr]['arguments']);
			$tmpoutput += array('opcode' => $opcode,
							'instruction' => $this->opcodes[$opcode]['mnemonic'],
							'args' => $args,
							'comments' => $comments,
							'value' => $arg,
							'printformat' => $this->opcodes[$opcode]['addressing']['printformat']);
			$output[] = $tmpoutput;
			$this->currentoffset += $size+1;
			
		}
		if (($this->branches === null) && isset(Main::get()->addresses[$this->initialoffset]['labels']))
			$this->branches = Main::get()->addresses[$this->initialoffset]['labels'];
		return $output;
	}
}

?>