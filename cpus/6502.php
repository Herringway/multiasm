<?php
class core {
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
		$this->opcodes = yaml_parse_file('./cpus/6502_opcodes.yml');
		$this->handle = $handle;
		require_once sprintf('platforms/%s.php', $opts['platform']);
		$this->platform = new platform($handle, $opts);
		$this->addrs = $known_addresses;
		$this->opts = $opts;
	}
	public function getDefault() {
		$realoffset = $this->platform->map_rom(0xFFFC);
		fseek($this->handle, $realoffset);
		$vector = ord(fgetc($this->handle)) + (ord(fgetc($this->handle))<<8);
		return $vector;
	}
	public function getMisc() {
		return array();
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
			$realoffset = $this->platform->map_rom($offset);
		} catch (Exception $e) {
			die (sprintf('Cannot disassemble: %s!', $e->getMessage()));
		}
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset($opts['size']))
			$deflength = $opts['size'];
		else if (isset($this->addrs[$this->initialoffset]['size']))
			$deflength = $this->addrs[$this->initialoffset]['size'];
		if (($realoffset < 0) || ($realoffset > $this->opts['size']))
			die (sprintf('Bad offset (%X)!', $realoffset));
		fseek($this->handle, $realoffset);
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
			if (isset($this->addrs[$this->initialoffset]['labels']) && isset($this->addrs[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $this->addrs[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$opcode = ord(fgetc($this->handle));
			$opcodeinfo = isset($this->opcodes[$opcode]) ? $this->opcodes[$opcode] : $this->opcodes['Undefined'];
			$uri = null;
			$name = '';
			$args = array();
			
			$size = $opcodeinfo['addressing']['size'];
			$arg = 0;
			for($j = 0; $j < $size; $j++) {
				$t = ord(fgetc($this->handle));
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			//$fulladdr = $this->fix_addr($opcode, $arg);
			$fulladdr = $arg;
			/*
			if ((($this->opcodes[$opcode]['addressing']['type'] == 'relative') || (($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') && isset($this->opcodes[$opcode]['addressing']['jump']))) && ($fulladdr + ($this->currentoffset&0xFF0000) > $farthestbranch))
				$farthestbranch = $fulladdr + ($this->currentoffset&0xFF0000);
				
			if ((($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') || ($this->opcodes[$opcode]['addressing']['type'] == 'absolutelongjmp'))) {
				if ((isset($this->addrs[$fulladdr]['name']) && !empty($this->addrs[$fulladdr]['name'])))
					$uri = $this->addrs[$fulladdr]['name'];
				else
					$uri = sprintf('%06X', $fulladdr);
			}
			if ($this->opcodes[$opcode]['addressing']['type'] == 'relativelong')
				$uri = sprintf('%06X', $fulladdr+($this->currentoffset&0xFF0000));
			if (isset($this->addrs[$fulladdr]['final processor state']['accum']))
				$accum = $this->addrs[$fulladdr]['final processor state']['accum'];
			if (isset($this->addrs[$fulladdr]['final processor state']['index']))
				$index = $this->addrs[$fulladdr]['final processor state']['index'];
			
			if (isset($this->addrs[$fulladdr]['name'])) {
				$name = $this->addrs[$fulladdr]['name'];
			} else if (isset($this->addrs[$this->initialoffset]['labels'][$fulladdr])) {
				$uri = sprintf('%s#%s', $offsetname, $this->addrs[$this->initialoffset]['labels'][$fulladdr]);
				$name = ($this->placeholdernames ? isset($this->addrs[$this->initialoffset]['name']) ? $this->addrs[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').$this->addrs[$this->initialoffset]['labels'][$fulladdr];
			} else if (($this->opcodes[$opcode]['addressing']['type'] == 'relative') || (($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') && (isset($this->opcodes[$opcode]['addressing']['jump'])))) {
				if (!isset($this->branches[$fulladdr]))
					$this->branches[$fulladdr] = '';
			} else if ($this->placeholdernames) {
				switch ($this->opcodes[$opcode]['addressing']['type']) {
					case 'absolute': $name = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutejmp': $name = sprintf('UNKNOWN_%04X', $arg); break;
					case 'absolutelongjmp': $name = sprintf('UNKNOWN_%06X', $arg); break;
					default: $name = ''; break;
				}
			} else
				$name = '';
				*/
			$arg = sprintf($opcodeinfo['addressing']['addrformat'], $arg,isset($args[0]) ? $args[0] : 0,isset($args[1]) ? $args[1] : 0, isset($args[2]) ? $args[2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($arg+$size+1,$size*8))&0xFFFF);
			
			$output[] = array('opcode' => $opcode,
							'instruction' => $opcodeinfo['mnemonic'],
							'offset' => $this->currentoffset,
							'args' => $args,
							'comment' => isset($this->addrs[$fulladdr]['description']) ? $this->addrs[$fulladdr]['description'] : '',
							'commentarguments' => isset($this->addrs[$fulladdr]['arguments']) ? $this->addrs[$fulladdr]['arguments'] : '',
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