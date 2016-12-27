<?php
class SPC700 extends platform {
	const extension = 'spc';
	
	public function map($offset) {
		if (($offset >= 0x10000) || ($offset < 0))
			throw new Exception("Out of range!");
		return array('ram', $offset);
	}
}
?>