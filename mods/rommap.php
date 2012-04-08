<?php
class rommap {
	private $main;
	
	const magic = 'rommap';
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$output = array();
		foreach ($this->main->addresses as $addr=>$data) {
			try {
				$realaddr = platform::get()->map_rom($addr);
				if ($realaddr !== null)
					$output[] = array('address' => isset($this->opts['real_address']) ? $realaddr : $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		$this->main->dataname = 'Rom Map';
		return array($output);
	}
}
?>