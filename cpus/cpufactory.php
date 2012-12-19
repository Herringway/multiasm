<?php
class cpuFactory {
	private function _construct() { }
	static function getCPU($name) {
		debugmessage('Getting CPU...'.$name, 'info');
		$name = strtolower($name);
		$subcpu = '';
		switch ($name) {
			case '65c816':
			case '65816':
				$cpu = '65816';
				break;
			case '6502':
				$cpu = '6502';
				break;
			case 'z80g':
				$subcpu = 'z80g';
			case 'z80':
				$cpu = 'z80';
				break;
			case 'arm':
				$cpu = 'ARM';
				break;
			case 'spc700':
				$cpu = 'SPC-700';
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