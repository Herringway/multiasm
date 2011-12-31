<?php
class platform {
	private $handle;
	private $opts;
	
	function __construct(&$handle,$opts) {
		$this->handle = $handle;
		$this->opts = $opts;
	}
	public function map_rom($offset) {
		return $offset;
	}
}
?>