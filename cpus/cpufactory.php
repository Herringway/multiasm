<?php
class cpuFactory {
	private function _construct() { }
	static function getCPU($name) {
		$name = strtolower($name);
		$subcpu = '';
		switch ($name) {
			case '65c816':
			case '65816':
				$cpu = '65816';
				break;
			case 'z80g':
				$subcpu = 'z80g';
			case 'z80':
				$cpu = 'z80';
				break;
			default:
				throw new Exception('Unrecognized CPU!');
		
		}
		require_once 'cpus/'.$cpu.'.php';
		$name = 'cpu_'.$cpu;
		return new $name($subcpu);
	}
}
?>