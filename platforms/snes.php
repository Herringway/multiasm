<?php
class platform {
	private $handle;
	private $opts;
	private $isHiROM;
	
	function __construct(&$handle,$opts) {
		$this->handle = $handle;
		$this->opts = $opts;
		$this->detectHiROM();
	}
	public function map_rom($offset) {
		if (($offset >= 0x7E0000) && ($offset < 0x800000))
			throw new Exception('RAM');
		else if ($this->isHiROM) {
			if ($offset&0x400000)
				return $offset&0x3FFFFF;
			else if ($offset&0x8000)
				return $offset&0x3FFFFF;
			else
				return -1;
		} else {
			if ($offset&0x400000)
				return $offset&0x3FFFFF;
			else if (!($offset&0x8000))
				return -1;
			else
				return (($offset&0x7F0000)>>1) + ($offset&0x7FFF);
		
		}
		throw new Exception('Unknown Area');
	}
	private function detectHiROM() {
		fseek($this->handle, 0x7FDC);
		$checksum = ord(fgetc($this->handle)) + (ord(fgetc($this->handle))<<8);
		$checksumcomplement = ord(fgetc($this->handle)) + (ord(fgetc($this->handle))<<8);
		$this->isHiROM = (($checksum^$checksumcomplement) != 0xFFFF);
	}
}
?>