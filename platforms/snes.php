<?php
class platform extends platform_base {
	private $isHiROM;
	
	const extension = 'sfc';
	
	function __construct() {
		$GLOBALS['addresses'] += yaml_parse_file('platforms/snes_registers.yml');
		if (isset($GLOBALS['game']['superfx']) && (Main::get()->game['superfx'] == true))
			$GLOBALS['addresses'] += yaml_parse_file('platforms/snes_superfx.yml');
		$this->detectHiROM();
	}
	public function map_rom($offset) {
		if (($offset > 0xFFFFFF) || ($offset < 0))
			throw new Exception('Out of range');
		if (($offset >= 0x7E0000) && ($offset < 0x800000))
			throw new Exception('RAM');
		else if ($this->isHiROM) {
			if ($offset&0x400000)
				return $offset&0x3FFFFF;
			else if (($offset&0x200000) && !($offset&0x400000) && ($offset&0xFFFF >= 0x6000) && ($offset&0xFFFF < 0x8000))
				throw new Exception('SRAM');
			else if ($offset&0x8000)
				return $offset&0x3FFFFF;
			else
				throw new Exception('Unknown');
		} else {
			if ($offset&0x400000)
				return $offset&0x3FFFFF;
			else if (!($offset&0x8000))
				throw new Exception('non-ROM');
			else
				return (($offset&0x7F0000)>>1) + ($offset&0x7FFF);
		
		}
		throw new Exception('Unknown Area');
	}
	public function map_ram($offset) {
		if (($offset > 0x7E0000) && ($offset < 0x800000))
			return $offset - 0x7E0000;
		else if (!$this->isHiROM) {
			if (($offset < 0x400000) && ($offset&0xFFFF < 0x2000))
				return $offset & 0xFFFF;
		}
		else
			throw new Exception('Not RAM');
	}
	private function detectHiROM() {
		global $rom;
		$rom->seekTo(0x7FDC);
		$checksum = $rom->getShort();
		$checksumcomplement = $rom->getShort();
		$this->isHiROM = (($checksum^$checksumcomplement) != 0xFFFF);
	}
}
?>