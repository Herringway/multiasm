<?php
class platform extends platform_base {
	private $details;
	const extension = 'gbc';
	
	function __construct() {
		$this->main = Main::get();
		$this->details['InitVector'] = sprintf('%04X', $this->main->rom->getShort(0x102));
		$this->main->rom->seekTo($this->map_rom(0x134));
		$this->details['InternalTitle'] = $this->main->rom->read(15);
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