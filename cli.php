<?php
class display {
	private $main;
	public $mode;
	
	function __construct(&$main) {
		$this->main = $main;
	}

	public function getArgv() {
		return array_slice($_SERVER['argv'], 1);
	}
	public function getOpts($argv) {
	}
	public function display($data) {
		switch ($this->mode) {
			case 'assembly':
			case 'snes': $this->disassemble($data); break;
			case 'table': $this->showtable($data); break;
			case 'rommap': $this->rommap($data); break;
			default: var_dump($this->mode); break;
		}
	}
	public static function debugvar($var, $label) {
		fwrite(STDERR, $label.': ');
		fwrite(STDERR, var_export($var, true).PHP_EOL);
	}
	private function disassemble($data) {
		foreach ($data as $instruction) {
			if (isset($instruction['label']))
				printf('%s: '.PHP_EOL, $instruction['label']);
			else
				printf("\t%s %s".PHP_EOL, $instruction['instruction'], $instruction['name'] !== '' ? $instruction['name'] : $instruction['value']);
		}
	}
	private function showtable($data) {
		echo yaml_emit($data['entries']);
	}
	private function rommap($data) {
		foreach ($data as $entry)
			printf(core::addressformat.' - '.core::addressformat.' ('.core::addressformat.'): %s'.PHP_EOL, $entry['address'], $entry['address'] + $entry['size'], $entry['size'], $entry['name'] === '' ? 'UNKNOWN' : $entry['name']);
	}
}
?>