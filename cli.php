<?php
class display {
	private $main;
	
	function __construct(&$main) {
		$this->main = $main;
	}

	public function getArgv() {
		return array_slice($_SERVER['argv'], 1);
	}
	public function getOpts($argv) {
	}
	public function display($data) {
		//foreach ($data as $k => $d)
			var_dump($data);
	}
}
?>