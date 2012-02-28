<?php
class core extends core_base {
	const addressformat = '%04X';
	
	private $opcodes;
	private $accum = 16;
	private $index = 16;
	public $placeholdernames = false;
	private $platform;
	function __construct(&$main) {
		$this->main = $main;
		$this->opcodes = yaml_parse_file('./cpus/6502_opcodes.yml');
	}
	public function getDefault() {
		$realoffset = $this->main->platform->map_rom(0xFFFC);
		fseek($this->main->gamehandle, $realoffset);
		return ord(fgetc($this->main->gamehandle)) + (ord(fgetc($this->main->gamehandle))<<8);
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
		try {
			$realoffset = $this->main->platform->map_rom($offset);
		} catch (Exception $e) {
			die (sprintf('Cannot disassemble: %s!', $e->getMessage()));
		}
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset($this->main->opts['size']))
			$deflength = $this->main->opts['size'];
		else if (isset($this->main->addresses[$this->initialoffset]['size']))
			$deflength = $this->main->addresses[$this->initialoffset]['size'];
		if (($realoffset < 0) || ($realoffset > $this->main->game['size']))
			die (sprintf('Bad offset (%X)!', $realoffset));
		fseek($this->main->gamehandle, $realoffset);
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
			if (isset($this->main->addresses[$this->initialoffset]['labels']) && isset($this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$opcode = ord(fgetc($this->main->gamehandle));
			$opcodeinfo = isset($this->opcodes[$opcode]) ? $this->opcodes[$opcode] : $this->opcodes['Undefined'];
			$uri = null;
			$name = '';
			$args = array();
			
			$size = $opcodeinfo['addressing']['size'];
			$arg = 0;
			for($j = 0; $j < $size; $j++) {
				$t = ord(fgetc($this->main->gamehandle));
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			$fulladdr = $this->fix_addr($opcodeinfo, $arg);
			
			if ($opcodeinfo['addressing']['type'] == 'absolutejmp') {
				if ((isset($this->main->addresses[$fulladdr]['name']) && !empty($this->main->addresses[$fulladdr]['name'])))
					$uri = $this->main->addresses[$fulladdr]['name'];
				else
					$uri = sprintf('%06X', $fulladdr);
			}
			if (($opcodeinfo['addressing']['type'] == 'relative') && ($fulladdr > $farthestbranch))
				$farthestbranch = $fulladdr;
			if (isset($this->main->addresses[$fulladdr]['name'])) {
				$name = $this->main->addresses[$fulladdr]['name'];
			} else if (isset($this->main->addresses[$this->initialoffset]['labels'][$fulladdr])) {
				$uri = sprintf('%s#%s', $offsetname, $this->main->addresses[$this->initialoffset]['labels'][$fulladdr]);
				$name = ($this->placeholdernames ? isset($this->main->addresses[$this->initialoffset]['name']) ? $this->main->addresses[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').$this->main->addresses[$this->initialoffset]['labels'][$fulladdr];
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
							'comment' => isset($this->main->addresses[$fulladdr]['description']) ? $this->main->addresses[$fulladdr]['description'] : '',
							'commentarguments' => isset($this->main->addresses[$fulladdr]['arguments']) ? $this->main->addresses[$fulladdr]['arguments'] : '',
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