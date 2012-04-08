<?php
class platform extends platform_base {
	private $details;
	
	const extension = 'nes';
	
	function __construct() {
		$flagarray = array();
		rom::get()->seekTo(0);
		$this->details['Valid'] = (rom::get()->read(4) == 'NES');
		$this->details['PRGROMSize'] = rom::get()->getByte()*0x4000;
		$this->details['CHRROMSize'] = rom::get()->getByte()*0x2000;
		$b1 = rom::get()->getByte();
		$b2 = rom::get()->getByte();
		$this->details['Mapper'] = (($b1&0xF0)>>4) + ($b2&0xF0);
		$flags = (($b1&0xF)<<20) + (($b2&0xF)<<16);
		$this->details['PRGRAMSize'] = rom::get()->getByte()*0x2000;
		$flags += rom::get()->getShort();
		for ($i = 0; $i < 24; $i++)
			$this->details['Flags'][(isset($flagarray[$i]) ? $flagarray[$i] : $i)] = (($flags & (1<<$i)) != 0);
		Main::get()->addresses += $this->getRegisters();
	}
	public function getRegisters() {
		return yaml_parse_file('platforms/nes_registers.yml') + (file_exists(sprintf('platforms/nes-mapper%d.yml', $this->details['Mapper'])) ? yaml_parse_file(sprintf('platforms/nes-mapper%d.yml', $this->details['Mapper'])) : array());
	}
	public function map_rom($offset) {
		if ($this->details['Mapper'] == 0) {
			if ($offset & 0x8000) {
				return ($offset & 0x7FFF) + 0x10;
			}
			throw new Exception('Not ROM');
		} else if ($this->details['Mapper'] == 4) {
			if ($offset & 0x8000) {
				if ($offset & 0x4000)
					return ($offset & 0x3FFF) + $this->details['PRGROMSize']-0x4000 + 0x10;
				if (($offset>>16)*4000 > $this->details['PRGROMSize'])
					throw new Exception('Out of range');
				return ($offset & 0x3FFF) + ($offset>>16)*0x4000;
			}
			throw new Exception('Not ROM');
		}
		throw new Exception(sprintf('Mapper %d unsupported',$this->details['Mapper']));
	}
	public function getMiscInfo() {
		return $this->details;
	}
}
?>