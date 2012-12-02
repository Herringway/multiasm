<?php
class cpuFactory {
	private function _construct() { }
	static function getCPU($name) {
		switch ($name) {
			case '65c816':
			case '65816':
				$cpu = '65816';
				break;
			default:
				throw new Exception('Unrecognized CPU!');
		
		}
		require_once 'cpus/'.$cpu.'.php';
		$name = 'cpu_'.$cpu;
		return new $name();
	}
}
?>