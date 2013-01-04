<?php
class snes extends platform {
	private $isHiROM;
	private $registers;
	
	function __construct() {
		if (!isset($this->registers))
			$this->registers = array();
		$this->registers += yaml_parse_file('platforms/snes_registers.yml');
		if (isset($this->game['superfx']) && ($this->game['superfx'] == true))
			$this->registers += yaml_parse_file('platforms/snes_superfx.yml');
	}
	public function map($offset) {
			if (($offset > 0xFFFFFF) || ($offset < 0))
				throw new Exception('Out of range');
			if (($offset >= 0x7E0000) && ($offset < 0x800000))
				return array('ram', $offset-0x7E0000);
			if ($offset&0x400000)
				return array('rom', ($offset&0x3FFFFF));
			$this->detectHiROM();
			if ($this->isHiROM) {
				if ((($offset > 0x200000) && ($offset < 0x3F0000) || (($offset > 0xA00000) && ($offset < 0xBF0000))) && (($offset&0xFFFF) >= 0x6000) && (($offset&0xFFFF) < 0x7FFF))
					return array('sram', ($offset&0x1FFF) + (($offset&0x1F0000)>>3));
				else if ($offset&0x8000)
					return array('rom', ($offset&0x3FFFFF));
				else
					throw new Exception('Unknown: '.dechex($offset));
			} else {
				if (($offset < 0x400000) && (($offset&0xFFFF) >= 0x2000) && (($offset & 0xFFFF) < 0x8000))
					return array('registers', ($offset&0xFFFF) - 0x2000);
				if (($offset < 0x400000) && ($offset&0xFFFF < 0x2000))
					return array('ram', $offset & 0xFFFF);
				else if (!($offset&0x8000))
					throw new Exception('Unknown');
				else
					return array('rom', (($offset&0x7F0000)>>1) + ($offset&0x7FFF));
			
			}
			throw new Exception('Unknown Area');
	}
	private function detectHiROM() {
		if (isset($this->isHiROM))
			return;
		$this->dataSource['rom']->seekTo(0x00FFDC);
		$checksum = $this->dataSource['rom']->getShort();
		$checksumcomplement = $this->dataSource['rom']->getShort();
		$this->isHiROM = (($checksum^$checksumcomplement) == 0xFFFF);
	}
	public function setDataSource(filter $source, $type = 'rom') { $this->dataSource[$type] = $source; }
	public function isInRange($offset) {
		try {
			list($source, $trueOffset) = $this->map($offset);
			debugvar($trueOffset, 'true offset');
			return $this->dataSource[$source]->isInRange($trueOffset);
		} catch (Exception $e) {
			dprintf('caught exception: %s', $e->msg());
		}
		return false;
	}
}
?>