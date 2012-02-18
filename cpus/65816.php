<?php
class core {
	const addressformat = '%06X';
	private $opcodes;
	public $initialoffset;
	public $currentoffset;
	public $branches;
	private $handle;
	private $accum = 16;
	private $index = 16;
	private $addrs;
	private $isHiROM;
	private $opts;
	public $placeholdernames = false;
	private $platform;
	function __construct(&$handle,$opts,&$known_addresses) {
		$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
		$this->handle = $handle;
		$this->platform = new platform($handle, $opts);
		$this->addrs = $known_addresses;
		$this->opts = $opts;
	}
	public function getDefault() {
		$realoffset = $this->platform->map_rom(0x00FFFC);
		fseek($this->handle, $realoffset);
		return ord(fgetc($this->handle)) + (ord(fgetc($this->handle))<<8);
	}
	public function getMisc() {
		$output = array();
		if (isset($this->opts['accum']))
			$output['accumsize'] = intval($this->opts['accum']);
		if (isset($this->opts['index']))
			$output['indexsize'] = intval($this->opts['index']);
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
		switch($this->opcodes[$instruction]['addressing']['type']) {
			case 'absolute':
				if (array_key_exists(0x7E0000+$val, $this->addrs))
					return 0x7E0000+$val;
			case 'absolutelong':
			case 'absolutelongjmp':
			case 'absolutelongindexed':
			case 'absolutelongindexedx':
				return $val;
			case 'absolutejmp':
				return ($this->currentoffset&0xFF0000)+$val;
			case 'absoluteindexedx':
			case 'absoluteindexedy':
				if (array_key_exists(0x7E0000+$val, $this->addrs))
					return 0x7E0000+$val;
				break;
			case 'relative':
				return ($this->currentoffset+uint($val+2,8))&0xFFFF;
			case 'relativelong':
				return ($this->currentoffset+uint($val+3,16))&0xFFFF;
		}
		return $val;
	}
	public function execute($offset,$offsetname) {
		try {
			$realoffset = $this->platform->map_rom($offset);
		} catch (Exception $e) {
			die (sprintf('Cannot disassemble %s!', $e->getMessage()));
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
		$index = isset($this->addrs[$this->initialoffset]['indexsize']) ? $this->addrs[$this->initialoffset]['indexsize'] : $this->index;
		$accum = isset($this->addrs[$this->initialoffset]['accumsize']) ? $this->addrs[$this->initialoffset]['accumsize'] : $this->accum;
		if (isset($this->opts['accum']))
			$accum = $this->opts['accum'];
		if (isset($this->opts['index']))
			$index = $this->opts['index'];
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
			$uri = null;
			$name = '';
			$args = array();
			
			if ($this->opcodes[$opcode]['addressing']['size'] === 'index')
				$size = $index/8;
			else if ($this->opcodes[$opcode]['addressing']['size'] === 'accum')
				$size = $accum/8;
			else
				$size = $this->opcodes[$opcode]['addressing']['size'];
			$arg = 0;
			for($j = 0; $j < $size; $j++) {
				$t = ord(fgetc($this->handle));
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			if (($opcode == 0xC2) | ($opcode == 0xE2)) {
				if ($args[0]&0x10)
					$index = ($opcode == 0xC2) ? 16 : 8;
				if ($args[0]&0x20)
					$accum = ($opcode == 0xC2) ? 16 : 8;
			}
			if ($opcode == 0x42)
				trigger_error("WDM Encountered. You sure this is assembly?");
				
			$fulladdr = $this->fix_addr($opcode, $arg);
			
			if (($this->opcodes[$opcode]['addressing']['type'] == 'relative') && ($fulladdr + ($this->currentoffset&0xFF0000) > $farthestbranch))
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
			} else if (isset($this->addrs[$this->initialoffset]['labels'][$fulladdr&0xFFFF])) {
				$uri = sprintf('%s#%s', $offsetname, $this->addrs[$this->initialoffset]['labels'][$fulladdr&0xFFFF]);
				$name = ($this->placeholdernames ? isset($this->addrs[$this->initialoffset]['name']) ? $this->addrs[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').$this->addrs[$this->initialoffset]['labels'][$fulladdr&0xFFFF];
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
				
			$arg = sprintf($this->opcodes[$opcode]['addressing']['addrformat'], $arg,isset($args[0]) ? $args[0] : 0,isset($args[1]) ? $args[1] : 0, isset($args[2]) ? $args[2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($arg+$size+1,$size*8))&0xFFFF);
			
			$output[] = array('opcode' => $opcode,
							'instruction' => $this->opcodes[$opcode]['mnemonic'],
							'offset' => $this->currentoffset,
							'args' => $args,
							'comment' => isset($this->addrs[$fulladdr]['description']) ? $this->addrs[$fulladdr]['description'] : '',
							'commentarguments' => isset($this->addrs[$fulladdr]['arguments']) ? $this->addrs[$fulladdr]['arguments'] : '',
							'name' => $name,
							'uri' => $uri,
							'value' => $arg,
							'printformat' => $this->opcodes[$opcode]['addressing']['printformat']);
			$this->currentoffset += $size+1;
			
		}
		return $output;
	}
}

?>