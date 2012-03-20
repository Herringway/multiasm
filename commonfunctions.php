<?php
register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
set_exception_handler('print_exception');
set_error_handler('error_handling');
define('BRANCH_LIMIT', 5000);
function print_exception($exception) {
	display::display_error(array('trace' => $exception->getTrace(), 'message' => $exception->getMessage()));
}
function error_handling($errno, $message, $file, $line) {
	static $errors = 0;
	//ini_set('display_errors', 'Off');
	if ($errors++ < 100)
		display::debugmessage(sprintf("%s on %s:%d", $message, $file, $line));
	return true;
}
function flagrant_system_error() {
	if (($error = error_get_last()) !== null) {
		if ($error['type'] == 1) {
			if (PHP_SAPI == 'cli')
				printf('SERIOUS ERROR: %s in %s:%d', $error['message'], $error['file'], $error['line']);
			else
				display::display_error(array('trace' => debug_backtrace(), 'message' => $error['message']));
		}
	} else {
		ob_end_flush();
	}
}
function hexafixer($matches) {
	if ($matches[0][0] === ' ')
		return sprintf(' 0x%04X:', $matches[1]);
	return sprintf('0x%06X:', $matches[1]);
}
function relative_to_absolute($offset, $val, $size) {
	return ($offset & 0xFF0000) + (($offset+uint($val+$size+1,$size*8))&0xFFFF);
}
function uint($i, $bits) {
	return $i < pow(2,$bits-1) ? $i : 0-(pow(2,$bits)-$i);
}
// function get_bit_flags($arg, $values) {
	// $output = array();
	// for ($i = 0; $i < 8; $i++)
		// if ($arg&pow(2,$i))
			// $output[] = $values[$i];
	// return implode(', ', $output);
// }
// function get_bit_flags2($arg, $values) {
	// $output = array();
	// for ($i = 0; $i < count($values); $i++)
		// if ($arg&pow(2,$i))
			// $output[] = $values[$i];
	// return $output;
// }
function asprintf($string, $haystack) {
	$output = array();
	foreach ($haystack as $needle)
		$output[] = vsprintf($string, $needle);
	return $output;
}
function load_settings() {
	if (!file_exists('settings.yml'))
		file_put_contents('settings.yml', yaml_emit(array('gameid' => 'eb', 'rompath' => '.', 'debug' => false, 'password' => 'changeme')));
	return yaml_parse_file('settings.yml');
}
abstract class platform_base {
	protected $main;
	public function getRegisters() {
		return array();
	}
	public function base() {
	}
	public function getMiscInfo() {
		return array();
	}
}

abstract class core_base {
	public $initialoffset;
	public $currentoffset;
	public $branches;
	public $placeholdernames = false;
	protected $main;
	const addressformat = '%X';
	const template = 'assembly';
	const opcodeformat = '%02X';
	function __construct() {
		$this->main = Backend::get();
	}
	public static function getRegisters() {
		return array();
	}
	public function getDefault() {
	}
	public function getMisc() {
		return array();
	}
}
?>
