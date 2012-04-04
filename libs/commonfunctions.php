<?php
register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
set_exception_handler('print_exception');
set_error_handler('error_handling');
ini_set('yaml.output_width', -1);
define('BRANCH_LIMIT', 5000);
function print_exception($exception) {
	display::get()->mode = 'error';
	display::get()->seterror();
	display::get()->display(array('trace' => $exception->getTrace(), 'message' => $exception->getMessage()));
}
function error_handling($errno, $message, $file, $line) {
	static $errors = 0;
	//ini_set('display_errors', 'Off');
	if ($errors++ < 100) {
		if (!class_exists('display'))
			printf("%s on %s:%d", $message, $file, $line);
		else
			display::debugmessage(sprintf("%s on %s:%d", $message, $file, $line));
	}
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
	//if ($matches[0][0] === ' ')
	//	return sprintf(' 0x%04X:', $matches[1]);
	return sprintf('0x%06X:', $matches[1]);
}
function hexafixer_human($matches) {
	static $i = 0;
	return sprintf('<a href="#'.core::addressformat.'" name="'.core::addressformat.'">%d ('.core::addressformat.'</a>)', $matches[1], $matches[1], $i++, $matches[1]);
}
function print_magical_yaml($file) {
	$output = yaml_emit($file, YAML_UTF8_ENCODING);
	$output = preg_replace_callback('/^(\d+):/m', 'hexafixer_human', $output);
	return $output;
}
function uint($i, $bits) {
	return $i < pow(2,$bits-1) ? $i : 0-(pow(2,$bits)-$i);
}
function asprintf($string, $haystack) {
	$output = array();
	foreach ($haystack as $needle)
		$output[] = vsprintf($string, $needle);
	return $output;
}
abstract class platform_base extends singleton {
	static $instance;
	protected $main;
	public function getRegisters() {
		return array();
	}
	public function getMiscInfo() {
		return array();
	}
	public function map_rom($offset) {
	}
	public function isROM($offset) {
		try {
			$this->map_rom($offset);
			return true;
		} catch (Exception $e) { }
		return false;
	}
}

abstract class core_base extends singleton {
	static $instance;
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
abstract class singleton {
	
	public static function get() {
		$class = get_called_class();
		if (!isset($class::$instance))
			$class::$instance = new $class();
		return $class::$instance;
	}
}
?>
