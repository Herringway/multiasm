<?php
class cpu_65816 extends cpucore {
	private $opcodes;
	private $accum = 16;
	private $index = 16;
	private $DBR;
	private $PBR;
	private $state;
	public static function addressFormat() {
		return '%06X';
	}
	function __construct() {
		$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
		$this->DBR = 0x007E;
	}
	public function getDefault() {
		$this->dataSource->seekTo(0xFFFC);
		return $this->dataSource->getShort();
	}
	public function getMisc() {
		$output = array();
		if (isset($GLOBALS['opts']['accum']))
			$output['accumsize'] = 8;
		if (isset($GLOBALS['opts']['index']))
			$output['indexsize'] = 8;
		return $output;
	}
	public static function getOptions() {
		return array(	array('adminonly' => false, 'label' => 'Initial 8-bit Index', 'type' => 'checkbox', 'id' => 'index', 'value' => 'true'),
						array('adminonly' => false, 'label' => 'Initial 8-bit Accum', 'type' => 'checkbox', 'id' => 'accum', 'value' => 'true'),
						array('adminonly' => false, 'label' => 'Simpler Output',      'type' => 'checkbox', 'id' => 'clean', 'value' => 'true'));
	}
	private function fix_addr($instruction, $val) {
		if (isset($this->opcodes[$instruction]['addressing']['UseDBR']))
			return ($this->DBR << 16) + $val;
		if (($this->opcodes[$instruction]['addressing']['type'] == 'relative') || ($this->opcodes[$instruction]['addressing']['type'] == 'relativelong'))
			return ($this->currentoffset+uint($val+2,8 * $this->opcodes[$instruction]['addressing']['size']))&0xFFFF;
		if (isset($this->opcodes[$instruction]['addressing']['UsePBR']))
			return ($this->PBR << 16) + $val;
		return $val;
	}
	private function getComments($opcode, $offset, $args) {
		$comments = array();
		if (($opcode == 0xC2) || ($opcode == 0xE2)) {
			$bits = '';
			$processor_bits = array('Carry', 'Zero', 'IRQ', 'Decimal', '8bit Index', '8bit Accum', 'Overflow', 'Negative');
			for ($i = 0; $i < 8; $i++)
				$bits .= $args[0]&pow(2,$i) ? $processor_bits[$i].' ' : '';
			$comments[($opcode == 0xC2 ? 'Unset' : 'Set')] = $bits;
		}
		/*if (isset($addresses[$offset]['description']))
			$comments['Description'] = $addresses[$offset]['description'];
		if (isset($addresses[$offset]['arguments']))
			foreach ($addresses[$offset]['arguments'] as $arg=>$val)
				$comments[$arg] = $val;*/
		return $comments;
	}
	public function execute($offset) {
		$this->dataSource->seekTo($offset);
		$this->PBR = $offset>>16;
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset($opts['size']))
			$lengthoverride = $opts['size'];
		else if (isset($addresses[$this->initialoffset]['size']))
			$lengthoverride = $addresses[$this->initialoffset]['size'];
		$tmpoutput['opcode'] = 0;
		$index = isset($addresses[$this->initialoffset]['indexsize']) ? $addresses[$this->initialoffset]['indexsize'] : $this->index;
		$accum = isset($addresses[$this->initialoffset]['accumsize']) ? $addresses[$this->initialoffset]['accumsize'] : $this->accum;
		if (isset($opts['accum']))
			$accum = 8;
		if (isset($opts['index']))
			$index = 8;
		$output = array();
		while (true) {
			if (isset($lengthoverride) && ($lengthoverride+$this->initialoffset <= $this->currentoffset)) //Allow overridden routine lengths
				break;
			if (($farthestbranch < $this->currentoffset) && !isset($lengthoverride) && isset($this->opcodes[$tmpoutput['opcode']]['addressing']['special']) && ($this->opcodes[$tmpoutput['opcode']]['addressing']['special'] == 'return'))
				break;
			if (($this->initialoffset&0xFF0000) != ($this->currentoffset&0xFF0000)) //Cannot cross bank boundaries
				break;
			$tmpoutput = array();
			$tmpoutput['offset'] = $this->currentoffset;
			if (isset($addresses[$this->initialoffset]['labels']) && isset($addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$tmpoutput['opcode'] = $this->dataSource->getByte();
			$tmpoutput['instruction'] = $this->opcodes[$tmpoutput['opcode']]['mnemonic'];
			$tmpoutput['args'] = array();
			
			if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'index')
				$size = $index/8;
			else if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'accum')
				$size = $accum/8;
			else
				$size = $this->opcodes[$tmpoutput['opcode']]['addressing']['size'];
			$tmpoutput['value'] = 0;
			for($j = 0; $j < $size; $j++) {
				$t = $this->dataSource->getByte();
				$tmpoutput['args'][] = $t;
				$tmpoutput['value'] += $t<<($j*8);
			}
			if (($tmpoutput['opcode'] == 0xC2) | ($tmpoutput['opcode'] == 0xE2)) {
				if ($tmpoutput['args'][0]&0x10)
					$index = ($tmpoutput['opcode'] == 0xC2) ? 16 : 8;
				if ($tmpoutput['args'][0]&0x20)
					$accum = ($tmpoutput['opcode'] == 0xC2) ? 16 : 8;
			}
			//if (isset($this->opcodes[$tmpoutput['opcode']]['undefined']))
			//	throw new Exception("Undefined opcode encountered. You sure this is assembly?");
				
			$fulladdr = $this->fix_addr($tmpoutput['opcode'], $tmpoutput['value']);
			
			if (($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative') && ($fulladdr + ($this->currentoffset&0xFF0000) > $farthestbranch))
				$farthestbranch = $fulladdr + ($this->currentoffset&0xFF0000);
				
			if ((($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'absolutejmp') || ($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'absolutelongjmp'))) {
				if (isset($addresses[$fulladdr]['name'])) {
					if ($this->dataSource->isInRange($fulladdr, 'rom'))
						$tmpoutput['uri'] = $addresses[$fulladdr]['name'];
				} else {
					if ($this->dataSource->isInRange($fulladdr, 'rom'))
						$tmpoutput['uri'] = sprintf('%06X', $fulladdr);
				}
			}
			if ($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relativelong')
				$tmpoutput['uri'] = sprintf('%06X', $fulladdr+($this->currentoffset&0xFF0000));
				
			if (isset($addresses[$fulladdr]['final processor state']['accum']))
				$accum = $addresses[$fulladdr]['final processor state']['accum'];
			if (isset($addresses[$fulladdr]['final processor state']['index']))
				$index = $addresses[$fulladdr]['final processor state']['index'];
			
			if (isset($addresses[$fulladdr]['name'])) {
				$tmpoutput['name'] = $addresses[$fulladdr]['name'];
				if ($platform->isROM($fulladdr))
					$tmpoutput['uri'] = $tmpoutput['name'];
			} else if ((($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative') || ($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'absolutejmp')) && isset($addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF])) {
				$tmpoutput['uri'] = sprintf('%s#%s', getOffsetName($this->initialoffset), $addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF]);
				$tmpoutput['name'] = ($this->dump ? isset($addresses[$this->initialoffset]['name']) ? $addresses[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').$addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF];
			} else if (($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative') || (($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'absolutejmp') && (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['jump'])))) {
				if (!isset($this->branches[$fulladdr]) && (count($this->branches) < BRANCH_LIMIT))
					$this->branches[$fulladdr] = '';
			} else if ($this->dump) {
				switch ($this->opcodes[$tmpoutput['opcode']]['addressing']['type']) {
					case 'absolute': $tmpoutput['name'] = sprintf('UNKNOWN_%04X', $tmpoutput['value']); break;
					case 'absolutejmp': $tmpoutput['name'] = sprintf('UNKNOWN_%04X', $tmpoutput['value']); break;
					case 'absolutelongjmp': $tmpoutput['name'] = sprintf('UNKNOWN_%06X', $tmpoutput['value']); break;
					default: $tmpoutput['name'] = ''; break;
				}
			}
			if (isset($game['localvars'])) {
				switch ($game['localvars']) {
					case 'directpage':
						if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['directpage']) && isset($addresses[$this->initialoffset]['localvars'][$tmpoutput['value']]))
							$tmpoutput['name'] = sprintf($settings['localvar format'], $addresses[$this->initialoffset]['localvars'][$tmpoutput['value']]);
						break;
				}
			}
			if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat']))
				$tmpoutput['value'] = sprintf($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat'], $tmpoutput['value'],isset($tmpoutput['args'][0]) ? $tmpoutput['args'][0] : 0,isset($tmpoutput['args'][1]) ? $tmpoutput['args'][1] : 0, isset($tmpoutput['args'][2]) ? $tmpoutput['args'][2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($tmpoutput['value']+$size+1,$size*8))&0xFFFF);

			$tmpoutput['comments'] = $this->getComments($tmpoutput['opcode'], $fulladdr, $tmpoutput['args']);
			if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['printformat']))
				$tmpoutput['printformat'] = $this->opcodes[$tmpoutput['opcode']]['addressing']['printformat'];
			$output[] = $tmpoutput;
			$this->currentoffset += $size+1;
			
		}
		if (($this->branches === null) && isset($addresses[$this->initialoffset]['labels']))
			$this->branches = $addresses[$this->initialoffset]['labels'];
		return $output;
	}
}

?>