<?php
class gbc extends platform {
	private $details;
	const extension = 'gbc';
	
	public function map($offset) {
		if (($offset&0xFFFF) >= 0x8000)
			throw new Exception('Not ROM');
		else if (($offset&0xFFFF) < 0x4000)
			return array('rom', ($offset&0xFFFF) + (($offset>>16)*0x4000));
		else
			return array('rom', ($offset&0xFFFF) + (($offset>>16)*0x4000));
	}
	public function getMiscInfo() {
		if (!isset($this->details)) {
			$this->dataSource['rom']->seekTo(0x102);
			$this->details['InitVector'] = sprintf('%04X', $this->dataSource['rom']->getShort());
			$this->dataSource['rom']->seekTo(0x134);
			$this->details['InternalTitle'] = $this->dataSource['rom']->getString(15);
		}
		return $this->details;
	}
}
?>
