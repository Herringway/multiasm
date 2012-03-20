<?php
class platform extends platform_base {
	const extension = 'gba';
	
	function __construct(&$main) {
		$this->main = $main;
	}
	public function map_rom($offset) {
		if ($offset > 0x8000000)
			return ($offset-0x8000000)&0x1FFFFFF;
		return -1;
	}


}
?>