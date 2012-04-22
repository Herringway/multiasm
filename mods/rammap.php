<?php
class rammap {
	private $main;
	
	const magic = 'rammap';
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$output = array();
		foreach ($this->main->addresses as $addr=>$data) {
			try {
				if (platform::get()->isRAM($addr) && !isset($data['ignore']))
					$output[] = array('address' => $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		$this->main->dataname = 'RAM Map';
		return array($output);
	}
}
?>