<?php
class snes extends platform {
	private $isHiROM;
	
	const extension = 'sfc';
	
	function __construct() {
		if (!is_array($GLOBALS['addresses']))
			$GLOBALS['addresses'] = array();
		$GLOBALS['addresses'] += yaml_parse_file('platforms/snes_registers.yml');
		if (isset($GLOBALS['game']['superfx']) && ($GLOBALS['game']['superfx'] == true))
			$GLOBALS['addresses'] += yaml_parse_file('platforms/snes_superfx.yml');
	}
	public function seekTo($offset) {
		list($source, $trueOffset) = $this->map($offset);
		$this->dataSource[$source]->seekTo($trueOffset);
	}
	public function map($offset) {
			/*if (!$this->isHiROM) {
				if (($offset < 0x400000) && ($offset&0xFFFF < 0x2000))
					return array('ram', $offset & 0xFFFF);
			}*/
			$this->detectHiROM();
			if (($offset > 0xFFFFFF) || ($offset < 0))
				throw new Exception('Out of range');
			if (($offset >= 0x7E0000) && ($offset < 0x800000))
				return array('ram', $offset-0x7E0000);
			else if ($this->isHiROM) {
				if ($offset&0x400000)
					return array('rom', ($offset&0x3FFFFF));
				else if (($offset&0x200000) && !($offset&0x400000) && ($offset&0xFFFF >= 0x6000) && ($offset&0xFFFF < 0x8000))
					return array('sram', ($offset&0xDFFFFF));
				else if ($offset&0x8000)
					return array('rom', ($offset&0x3FFFFF));
				else
					throw new Exception('Unknown');
			} else {
				if ($offset&0x400000)
					return array('rom', ($offset&0x3FFFFF));
				else if (!($offset&0x8000))
					throw new Exception('non-ROM');
				else
					return array('rom', (($offset&0x7F0000)>>1) + ($offset&0x7FFF));
			
			}
			throw new Exception('Unknown Area');
	}
	private function detectHiROM() {
		if (isset($this->isHiROM))
			return;
		$this->dataSource['rom']->seekTo(0x7FDC);
		$checksum = $this->dataSource['rom']->getShort();
		$checksumcomplement = $this->dataSource['rom']->getShort();
		$this->isHiROM = (($checksum^$checksumcomplement) != 0xFFFF);
	}
	public function setDataSource(filter $source) {
		$this->dataSource['rom'] = $source;
	}
	public function setSRAMSource(filter $source) {
		$this->dataSource['sram'] = $source;
	}
	public function setRAMSource(filter $source) {
		$this->dataSource['ram'] = $source;
	}
	public function isInRange($offset) {
		try {
			list($source, $trueOffset) = $this->map($offset);
			return $this->dataSource[$source]->isInRange($trueOffset);
		} catch (Exception $e) {
		}
		return false;
	}
	public function getByte() {
		return $this->dataSource['rom']->getByte();
	}
	public function getShort() {
		return $this->dataSource['rom']->getShort();
	}
	public function getLong() {
		return $this->dataSource['rom']->getLong();
	}
}
?>