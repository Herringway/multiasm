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
		$this->main = Main::get();
		$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
		$this->DBR = 0x007E;
	}
	public function getDefault() {
		return $this->main->rom->getShort($this->main->platform->map_rom(0x00FFFC));
	}
	public function getMisc() {
		$output = array();
		if (isset($this->main->opts['accum']))
			$output['accumsize'] = 8;
		if (isset($this->main->opts['index']))
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
		$realoffset = $this->main->platform->map_rom($offset);
		$this->PBR = $offset>>16;
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		if (isset($this->main->opts['size']))
			$deflength = $this->main->opts['size'];
		else if (isset($this->main->addresses[$this->initialoffset]['size']))
			$deflength = $this->main->addresses[$this->initialoffset]['size'];
		if (($realoffset < 0) || ($realoffset > $this->main->game['size']))
			throw new Exception (sprintf('Bad offset (%X)!', $realoffset));
		$this->main->rom->seekTo($realoffset);
		$unknownbranches = 0;
		$opcode = 0;
		$index = isset($this->main->addresses[$this->initialoffset]['indexsize']) ? $this->main->addresses[$this->initialoffset]['indexsize'] : $this->index;
		$accum = isset($this->main->addresses[$this->initialoffset]['accumsize']) ? $this->main->addresses[$this->initialoffset]['accumsize'] : $this->accum;
		if (isset($this->main->opts['accum']))
			$accum = 8;
		if (isset($this->main->opts['index']))
			$index = 8;
		$output = array();
		while (true) {
			unset($comment);
			if (isset($deflength) && ($deflength+$this->initialoffset <= $this->currentoffset))
				break;
			if (($farthestbranch < $this->currentoffset) && !isset($deflength) && isset($this->opcodes[$opcode]['addressing']['special']) && ($this->opcodes[$opcode]['addressing']['special'] == 'return'))
				break;
			if (($this->initialoffset&0xFF0000) != ($this->currentoffset&0xFF0000))
				break;
			if (isset($this->main->addresses[$this->initialoffset]['labels']) && isset($this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $this->main->addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$opcode = $this->main->rom->getByte();
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
				$t = $this->main->rom->getByte();
				$args[] = $t;
				$arg += $t<<($j*8);
			}
			if (($opcode == 0xC2) | ($opcode == 0xE2)) {
				$comment = ($opcode == 0xC2 ? 'Unset: ' : 'Set: ').$this->get_processor_bits($args[0]);
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
				if (isset($this->main->addresses[$fulladdr]['name'])) {
					if ($this->main->platform->isROM($fulladdr))
						$uri = $this->main->addresses[$fulladdr]['name'];
				} else {
					if ($this->main->platform->isROM($fulladdr))
						$uri = sprintf('%06X', $fulladdr);
				}
			}
			if ($this->opcodes[$opcode]['addressing']['type'] == 'relativelong')
				$uri = sprintf('%06X', $fulladdr+($this->currentoffset&0xFF0000));
			if (isset($this->main->addresses[$fulladdr]['final processor state']['accum']))
				$accum = $this->main->addresses[$fulladdr]['final processor state']['accum'];
			if (isset($this->main->addresses[$fulladdr]['final processor state']['index']))
				$index = $this->main->addresses[$fulladdr]['final processor state']['index'];
			
			if (isset($this->main->addresses[$fulladdr]['name'])) {
				$name = $this->main->addresses[$fulladdr]['name'];
				if ($this->main->platform->isROM($fulladdr))
					$uri = $name;
			} else if ((($this->opcodes[$opcode]['addressing']['type'] == 'relative') || ($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp')) && isset($this->main->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF])) {
				$uri = sprintf('%s#%s', $this->main->getOffsetName($this->initialoffset), $this->main->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF]);
				$name = ($this->placeholdernames ? isset($this->main->addresses[$this->initialoffset]['name']) ? $this->main->addresses[$this->initialoffset]['name'].'_' : sprintf('UNKNOWN_%06X_', $this->initialoffset) : '').$this->main->addresses[$this->initialoffset]['labels'][$fulladdr&0xFFFF];
			} else if (($this->opcodes[$opcode]['addressing']['type'] == 'relative') || (($this->opcodes[$opcode]['addressing']['type'] == 'absolutejmp') && (isset($this->opcodes[$opcode]['addressing']['jump'])))) {
				if (!isset($this->branches[$fulladdr]) && (count($this->branches) < BRANCH_LIMIT))
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
			if (isset($this->main->game['localvars'])) {
				switch ($this->main->game['localvars']) {
					case 'directpage':
						if (isset($this->opcodes[$opcode]['addressing']['directpage']) && isset($this->main->addresses[$this->initialoffset]['localvars'][$arg]))
							$name = sprintf($this->main->settings['localvar format'], $this->main->addresses[$this->initialoffset]['localvars'][$arg]);
						break;
				}
			}
			$arg = sprintf($this->opcodes[$opcode]['addressing']['addrformat'], $arg,isset($args[0]) ? $args[0] : 0,isset($args[1]) ? $args[1] : 0, isset($args[2]) ? $args[2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($arg+$size+1,$size*8))&0xFFFF);
			
			$output[] = array('opcode' => $opcode,
							'instruction' => $this->opcodes[$opcode]['mnemonic'],
							'offset' => $this->currentoffset,
							'args' => $args,
							'comment' => isset($comment) ? $comment : (isset($this->main->addresses[$fulladdr]['description']) ? $this->main->addresses[$fulladdr]['description'] : ''),
							'commentarguments' => isset($this->main->addresses[$fulladdr]['arguments']) ? $this->main->addresses[$fulladdr]['arguments'] : '',
							'name' => $name,
							'uri' => $uri,
							'value' => $arg,
							'printformat' => $this->opcodes[$opcode]['addressing']['printformat']);
			$this->currentoffset += $size+1;
			
		}
		if (($this->branches === null) && isset($this->main->addresses[$this->initialoffset]['labels']))
			$this->branches = $this->main->addresses[$this->initialoffset]['labels'];
		return $output;
	}
}

?>