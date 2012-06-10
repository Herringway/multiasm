<?php
class platform extends platform_base {
	const extension = 'spc';
	
	public function map_rom($offset) {
		if ($offset < 0x10000)
			return $offset+0x200;
		else
			throw new Exception("Supplied offset too large!");
	}
}
?>