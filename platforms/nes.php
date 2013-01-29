<?php
class nes extends platform {
	private $details;
	
	function init() {
		$flagarray = array();
		$this->dataSource['rom']->seekTo(0);
		$this->details['Valid'] = ($this->dataSource['rom']->getString(4) == 'NES');
		$this->details['PRGROMSize'] = $this->dataSource['rom']->getByte()*0x4000;
		$this->details['CHRROMSize'] = $this->dataSource['rom']->getByte()*0x2000;
		$b1 = $this->dataSource['rom']->getByte();
		$b2 = $this->dataSource['rom']->getByte();
		$this->details['Mapper'] = (($b1&0xF0)>>4) + ($b2&0xF0);
		$flags = (($b1&0xF)<<20) + (($b2&0xF)<<16);
		$this->details['PRGRAMSize'] = $this->dataSource['rom']->getByte()*0x2000;
		$flags += $this->dataSource['rom']->getShort();
		for ($i = 0; $i < 24; $i++)
			$this->details['Flags'][(isset($flagarray[$i]) ? $flagarray[$i] : $i)] = (($flags & (1<<$i)) != 0);
	}
	public function map($offset) {
		if ($this->details['Mapper'] == 0) {
			if ($offset & 0x8000) {
				return array('rom', ($offset & 0x7FFF) + 0x10);
			}
			throw new Exception('Not ROM');
		} else if ($this->details['Mapper'] == 4) {
			if ($offset & 0x8000) {
				if ($offset & 0x4000)
					return array('rom', ($offset & 0x3FFF) + $this->details['PRGROMSize']-0x4000 + 0x10);
				if (($offset>>16)*4000 > $this->details['PRGROMSize'])
					throw new Exception('Out of range');
				return array('rom', ($offset & 0x3FFF) + ($offset>>16)*0x4000);
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