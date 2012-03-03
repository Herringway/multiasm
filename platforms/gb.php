<?php
class platform extends platform_base {
	private $details;
	const extension = 'gb';
	
	function __construct($main) {
		$this->main = $main;
		fseek($main->gamehandle, $this->map_rom(0x102));
		$this->details['InitVector'] = sprintf('%04X', ord(fgetc($main->gamehandle)) + (ord(fgetc($main->gamehandle))<<8));
		fseek($main->gamehandle, $this->map_rom(0x134));
		$this->details['InternalTitle'] = fread($main->gamehandle, 15);
	}
	public function map_rom($offset) {
		if (($offset&0xFFFF) >= 0x8000)
			throw new Exception('Not ROM');
		else if (($offset&0xFFFF) < 0x4000)
			return $offset&0xFFFF;
		else
			return ($offset&0xFFFF) + (($offset>>16)*0x4000);
	}
	public function getMiscInfo() {
		return $this->details;
	}
}
?>