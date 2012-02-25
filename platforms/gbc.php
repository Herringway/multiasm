<?php
class platform extends platform_base {
	private $handle;
	private $opts;
	
	const extension = 'gbc';
	
	function __construct(&$main) {
		$this->handle = $main->gamehandle;
		$this->opts = $main->opts;
	}
	public function map_rom($offset) {
		throw new Exception("Incomplete");
	}
}
?>