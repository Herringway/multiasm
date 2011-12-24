<?php
class platform {
	private $handle;
	private $opts;
	private $isHiROM;
	
	function __construct(&$handle,$opts) {
		$this->handle = $handle;
		$this->opts = $opts;
	}
	public function map_rom($offset) {
		if ($offset > 0x8000000)
			return ($offset-0x8000000)&0x1FFFFFF;
		return -1;
	}


}
?>