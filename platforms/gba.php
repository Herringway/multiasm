<?php
class platform extends platform_base {
	private $handle;
	private $opts;
	private $isHiROM;
	const extension = 'gba';
	
	function __construct(&$main) {
		$this->handle = $main->gamehandle;
		$this->opts = $main->opts;
	}
	public function map_rom($offset) {
		if ($offset > 0x8000000)
			return ($offset-0x8000000)&0x1FFFFFF;
		return -1;
	}


}
?>