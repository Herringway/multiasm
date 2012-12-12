<?php
class cpu_65816 extends cpucore {
	private $opcodes;
	private $processorFlags;
	private $DBR;
	private $PBR;
	public static function addressFormat() {
		return '%06X';
	}
	public function initializeProcessor() {
		$this->PBR = 0x0000;
		$this->DBR = 0x007E;
		$this->processorFlags['8 Bit Accum'] = true;
		$this->processorFlags['8 Bit Index'] = true;
		$this->processorFlags['Emulation'] = true;
	}
	public function getDefault() {
		$this->dataSource->seekTo(0xFFFC);
		return $this->dataSource->getShort();
	}
	public function getOpcodes() {
		return $this->opcodes;
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
		return $comments;
	}
	public function execute($offset, $length = 0xFFFF) {
		if (!isset($this->opcodes))
			$this->opcodes = yaml_parse_file('./cpus/65816_opcodes.yml');
		$this->initializeProcessor();
		$this->dataSource->seekTo($offset);
		$this->PBR = $offset>>16;
		$farthestbranch = $this->initialoffset = $this->currentoffset = $offset;
		$output = array();
		while (true) {
			if ($this->currentoffset - $this->initialoffset >= $length)
				break;
			if (($farthestbranch < $this->currentoffset) && isset($this->opcodes[$tmpoutput['opcode']]['addressing']['special']) && ($this->opcodes[$tmpoutput['opcode']]['addressing']['special'] == 'return'))
				break;
			if (($this->initialoffset&0xFF0000) != ($this->currentoffset&0xFF0000)) //Cannot cross bank boundaries
				break;
			$tmpoutput = array();
			$tmpoutput['offset'] = $this->currentoffset;
			if (isset($addresses[$this->initialoffset]['labels']) && isset($addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]))
				$output[] = array('label' => $addresses[$this->initialoffset]['labels'][$this->currentoffset&0xFFFF]);
			$tmpoutput['opcode'] = $this->dataSource->getByte();
			$tmpoutput['args'] = array();
			
			if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'index')
				$size = !$this->processorFlags['8 Bit Index']+1;
			else if ($this->opcodes[$tmpoutput['opcode']]['addressing']['size'] === 'accum')
				$size = !$this->processorFlags['8 Bit Accum']+1;
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
					$this->processorFlags['8 Bit Index'] = ($tmpoutput['opcode'] != 0xC2);
				if ($tmpoutput['args'][0]&0x20)
					$this->processorFlags['8 Bit Accum'] = ($tmpoutput['opcode'] != 0xC2);
			}
			if (isset($this->opcodes[$tmpoutput['opcode']]['undefined']))
				throw new Exception("Undefined opcode encountered.");
				
			$fulladdr = $this->fix_addr($tmpoutput['opcode'], $tmpoutput['value']);
			
			if ($this->opcodes[$tmpoutput['opcode']]['addressing']['type'] == 'relative')
				$farthestbranch = max($farthestbranch, $fulladdr + ($this->currentoffset&0xFF0000));
			//if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['directpage']) && isset($addresses[$this->initialoffset]['localvars'][$tmpoutput['value']]))
			//	$tmpoutput['name'] = sprintf($settings['localvar format'], $addresses[$this->initialoffset]['localvars'][$tmpoutput['value']]);
				
			if (isset($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat']))
				$tmpoutput['value'] = sprintf($this->opcodes[$tmpoutput['opcode']]['addressing']['addrformat'], $tmpoutput['value'],isset($tmpoutput['args'][0]) ? $tmpoutput['args'][0] : 0,isset($tmpoutput['args'][1]) ? $tmpoutput['args'][1] : 0, isset($tmpoutput['args'][2]) ? $tmpoutput['args'][2] : 0, $this->currentoffset>>16, ($this->currentoffset+uint($tmpoutput['value']+$size+1,$size*8))&0xFFFF);

			$output[] = $tmpoutput;
			$this->currentoffset += $size+1;
			
		}
		if (($this->branches === null) && isset($addresses[$this->initialoffset]['labels']))
			$this->branches = $addresses[$this->initialoffset]['labels'];
		return $output;
	}
}

?>