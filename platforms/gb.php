<?php
class platform extends platform_base {
	private $handle;
	private $opts;
	
	const extension = 'gb';
	
	function __construct($main) {
		$this->handle = $main->gamehandle;
		$this->opts = $main->opts;
	}
	public function map_rom($offset) {
		if (($offset&0xFFFF) >= 0x8000)
			throw new Exception('Not ROM');
		else if (($offset&0xFFFF) < 0x4000)
			return $offset&0xFFFF;
		else
			return ($offset&0xFFFF) + (($offset>>16)*0x4000);
		//throw new Exception("GBC platform implementation lacks rom mapping!");
	}
}
?>