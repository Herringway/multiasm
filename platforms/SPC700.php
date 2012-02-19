<?php
class platform extends platform_base {
	private $handle;
	private $opts;
	const extension = 'spc';
	
	function __construct(&$handle,$opts) {
		$this->handle = $handle;
		$this->opts = $opts;
	}
	public function map_rom($offset) {
		return $offset+0x200;
	}
}
?>