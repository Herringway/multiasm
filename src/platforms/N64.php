<?php
class N64 extends platform {
	public function getPlatformAddresses() {
		return [];
	}
	public function map($offset) {
		if (($offset >= 0x10000000) && ($offset < 0x1FC00000))
			return array('rom', $offset-0x10000000);
		if (($offset >= 0x1FC00000) && ($offset < 0x1FD00000))
			return array('firmware', $offset-0x1FC00000);
		if ($offset < 0x800000)
			return array('ram', $offset);
		throw new Exception('Unknown Area');
	}
}
?>