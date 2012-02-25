<?php
class platform extends platform_base {
	const extension = 'spc';
	
	function __construct(&$main) {
	}
	public function map_rom($offset) {
		return $offset+0x200;
	}
}
?>