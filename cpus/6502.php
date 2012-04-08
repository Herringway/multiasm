<?php
class core extends core_base {
	const addressformat = '%04X';
	
	private $opcodes;
	private $accum = 16;
	private $index = 16;
	public $placeholdernames = false;
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/6502_opcodes.yml');
	}
	public function getDefault() {
		return rom::get()->getShort(platform::get()->map_rom(0xFFFC));
	}
	private function fix_addr($opcode, $offset) {
		if ($opcode['addressing']['type'] == 'relative')
			return (($this->currentoffset+uint($offset+2,8))&0xFFFF) + ($this->currentoffset&0xFF0000);
		else if ($opcode['addressing']['type'] == 'absolute')
			return ($this->initialoffset&0xFF0000) + $offset;
		else if ($opcode['addressing']['type'] == 'absolutejmp')
			return ($this->initialoffset&0xFF0000) + $offset;
		return -1;
	}
	private function get_processor_bits($arg) {
		$output = '';
		$processor_bits = array('Carry', 'Zero', 'IRQ', 'Decimal', '', '', 'Overflow', 'Negative');
		for ($i = 0; $i < 8; $i++)
			$output .= $arg&pow(2,$i) ? $processor_bits[$i].' ' : '';

		return $output;
	}
	public function execute($offset,$offsetname) {
		$realoffset = platform::get()->map_rom($offset);
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset(Main::get()->opts['size']))
			$deflength = Main::get()->opts['size'];
		else if (isset(Main::get()->addresses[$this->initialoffset]['size']))
			$deflength = Main::get()->addresses[$this->initialoffset]['size'];
		if (($realoffset < 0) || ($realoffset > Main::get()->game['size']))
			die (sprintf('Bad offset (%X)!', $realoffset));
		rom::get()->seekTo($realoffset);
		$unknownbranches = 0;
		$opcode = 0;
		$output = array();
		while (true) {
			if (isset($deflength) && ($deflength+$this->initialoffset <= $this->currentoffset))
				break;
			if (($farthestbranch < $this->currentoffset) && !isset($deflength) && isset($this->opcodes[$opcode]['addressing']['special']) && ($this->opcodes[$opcode]['addressing']['special'] == 'return'))
				break;
			if (($this->initialoffset&0xFF0000) != ($this->currentoffset&0xFF0000))
				break;
			if (isset(Main::get()->addresses[$this->initialoffset]['labels']) && isset(Main::get()->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => Main::get()->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$opcode = rom::get()->getByte();
			$opcodeinfo = isset($this->opcodes[$opcode]) ? $this->opcodes[$opcode] : $this->opcodes['Undefined'];
			$uri = null;
			$name = '';
			$args = array();
			
			$size = $opcodeinfo['addressing']['size'];
			$arg = 0;
			for($j = 0; $j < $size; $j++) {
				$t = rom::get()->getByte();
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			$fulladdr = $this->fix_addr($opcodeinfo, $arg);
			
			if ($opcodeinfo['addressing']['type'] == 'absolutejmp') {
				if ((isset(Main::get()->addresses[$fulladdr]['name']) && !empty(Main::get()->addresses[$fulladdr]['name'])))
					$uri = Main::get()->addresses[$fulladdr]['name'];
				else
					$uri = sprintf('%06X', $fulladdr);
			}
			if (($opcodeinfo['addressing']['type'] == 'relative') && ($fulladdr > $farthestbranch))
				$farthestbranch = $fulladdr;
			if (isset(Main::get()->addresses[$fulladdr]['name'])) {
				$name = Main::get()->addresses[$fulladdr]['name'];
			} else if (isset(Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr])) {
				$uri = sprintf('%s#%s', $offsetname, Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr]);
				$name = ($this->placeholdernames ? isset(Main::get()->addresses[$this->initialoffset]['name']) ? Main::get()->addresses[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').Main::get()->addresses[$this->initialoffset]['labels'][$fulladdr];
			} else if ($opcodeinfo['addressing']['type'] == 'relative') {
				if (!isset($this->branches[$fulladdr]))
					$this->branches[$fulladdr] = '';
			} else if ($this->placeholdernames) {
				switch ($opcodeinfo['addressing']['type']) {
					case 'absolute': $name = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutejmp': $name = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutelongjmp': $name = sprintf('UNKNOWN_%06X', $arg); break;
					default: $name = ''; break;
				}
			} else
				$name = '';
			$arg = sprintf($opcodeinfo['addressing']['addrformat'], $arg,isset($args[0]) ? $args[0] : 0,isset($args[1]) ? $args[1] : 0, isset($args[2]) ? $args[2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($arg+$size+1,$size*8))&0xFFFF);
			
			$output[] = array('opcode' => $opcode,
							'instruction' => $opcodeinfo['mnemonic'],
							'offset' => $this->currentoffset,
							'args' => $args,
							'comment' => isset(Main::get()->addresses[$fulladdr]['description']) ? Main::get()->addresses[$fulladdr]['description'] : '',
							'commentarguments' => isset(Main::get()->addresses[$fulladdr]['arguments']) ? Main::get()->addresses[$fulladdr]['arguments'] : '',
							'name' => $name,
							'uri' => $uri,
							'value' => $arg,
							'printformat' => $opcodeinfo['addressing']['printformat']);
			$this->currentoffset += $size+1;
			
		}
		return $output;
	}
}

?>