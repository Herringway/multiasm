<?php
class platform {
	private $handle;
	private $opts;
	private $details;
	
	function __construct(&$handle,$opts) {
		$this->handle = $handle;
		$this->opts = $opts;
		$flagarray = array();
		fseek($handle, 0);
		$this->details['Valid'] = (fread($handle, 4) == 'NES');
		$this->details['PRGROMSize'] = ord(fgetc($handle))*0x4000;
		$this->details['CHRROMSize'] = ord(fgetc($handle))*0x2000;
		$b1 = ord(fgetc($handle));
		$b2 = ord(fgetc($handle));
		$this->details['Mapper'] = (($b1&0xF0)>>4) + ($b2&0xF0);
		$flags = (($b1&0xF)<<20) + (($b2&0xF)<<16);
		$this->details['PRGRAMSize'] = ord(fgetc($handle))*0x2000;
		$flags += (ord(fgetc($handle))<<8) + ord(fgetc($handle));
		for ($i = 0; $i < 24; $i++)
			$this->details['Flags'][(isset($flagarray[$i]) ? $flagarray[$i] : $i)] = (($flags & (1<<$i)) != 0);
	}
	public function map_rom($offset) {
		if ($this->details['Mapper'] == 4) {
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
}
?>