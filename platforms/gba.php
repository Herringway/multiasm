<?php
class platform extends platform_base {
	const extension = 'gba';
	
	public function map_rom($offset) {
		if ($offset > 0x8000000)
			return ($offset-0x8000000)&0x1FFFFFF;
		throw new Exception("NOT ROM");
	}


}
?>