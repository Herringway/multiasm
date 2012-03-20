<?php
class platform extends platform_base {
	const extension = 'spc';
	
	function __construct(&$main) {
	}
	public function map_rom($offset) {
		if ($offset < 0x10000)
			return $offset+0x200;
		else
			throw new Exception("this, address is only 16-bit");
	}
}
?>