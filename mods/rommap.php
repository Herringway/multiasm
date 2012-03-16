<?php
class rommap {
	private $main;
	
	const magic = 'rommap';
	function __construct(&$main) {
		$this->main = $main;
	}
	public function execute() {
		$output = array();
		foreach ($this->main->addresses as $addr=>$data) {
			try {
				$realaddr = $this->main->platform->map_rom($addr);
				if ($realaddr !== null)
					$output[] = array('address' => isset($this->opts['real_address']) ? $realaddr : $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		$this->main->dataname = 'Rom Map';
		$this->main->yamldata[] = $output;
		return $output;
	}
}
?>