<?php
class gba extends platform {
	const extension = 'gba';
	
	public function map($offset) {
		if ($offset <= 0x4000)
			return array('bios', $offset);
		else if ($offset < 0x02000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x02040000)
			return array('WRAM1', $offset - 0x02000000);
		else if ($offset < 0x03000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x03008000)
			return array('WRAM1', $offset - 0x02000000);
		else if ($offset < 0x04000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x05000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x06000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x07000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x07004000)
			return array('OAM', $offset - 0x07000000);
		else if ($offset < 0x08000000)
			throw new Exception('UNKNOWN');
		else if ($offset < 0x0E000000)
			return array('rom', $offset-0x8000000);
		else if ($offset < 0x0E010000)
			return array('sram', $offset-0xE000000);
		else
			throw new Exception('UNKNOWN');
	}

}
?>