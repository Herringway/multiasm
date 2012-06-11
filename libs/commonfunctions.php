<?php
register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
set_exception_handler('print_exception');
set_error_handler('error_handling');
ini_set('yaml.output_width', -1);
define('BRANCH_LIMIT', 5000);
function print_exception($exception) {
	global $display;
	$display->mode = 'error';
	$display->seterror();
	$display->display(array('trace' => $exception->getTrace(), 'message' => $exception->getMessage()));
}
function error_handling($errno, $message, $file, $line) {
	static $errors = 0;
	//ini_set('display_errors', 'Off');
	if ($errors++ < $GLOBALS['settings']['errorlimit']) {
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
function json($obj) {
	return json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
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
abstract class platform_base {
	protected $main;
	public function getRegisters() {
		return array();
	}
	public function getMiscInfo() {
		return array();
	}
	public function map_rom($offset) {
	}
	public function map_ram($offset) {
	}
	public function isROM($offset) {
		try {
			$this->map_rom($offset);
			return true;
		} catch (Exception $e) { }
		return false;
	}
	public function isRAM($offset) {
		try {
			$this->map_ram($offset);
			return true;
		} catch (Exception $e) { }
		return false;
	}
}
function defaultv($format) {
	if (isset($format))
		return $format;
	return '%s';
}
abstract class gamemod {
	const title = '';
	public function description() {
		return $this::title;
	}
}
abstract class core_base {
	public $initialoffset;
	public $currentoffset;
	public $branches;
	public $placeholdernames = false;
	public $dump = false;
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
	public static function getOptions() {
		return array();
	}
	public function getDefault() {
	}
	public function getMisc() {
		return array();
	}
}
function getOffsetName($offset, $onlyifexists = false) {
	global $addresses;
	if ($onlyifexists)
		return isset($addresses[$offset]['name']) ? $addresses[$offset]['name'] : '';
	return isset($addresses[$offset]['name']) ? $addresses[$offset]['name'] : sprintf(core::addressformat, $offset);
}
function getDescription($offset, $onlyifexists = false) {
	global $addresses;
	if ($onlyifexists)
		return isset($addresses[$offset]['description']) ? $addresses[$offset]['description'] : '';
	if (isset($addresses[$offset]['description']))
		return $addresses[$offset]['description'];
	if (isset($addresses[$offset]['name']))
		return $addresses[$offset]['name'];
	return sprintf(core::addressformat, $offset);
}
function gametitle($game) {
	$miscdata = array();
	if (isset($game['country']))
		$miscdata[] = $game['country'];
	if (isset($game['version']))
		$miscdata[] = 'v'.$game['version'];
	return $game['title'].(($miscdata != array()) ? ' ('.implode(' ', $miscdata).')' : '');
}
function getDataBlock($ioffset) {
	global $addresses;
	$offset = $ioffset;
	for (;!isset($addresses[$offset]) && ($offset > 0); $offset--);
	if (!isset($addresses[$offset]) || ($ioffset - $offset > $addresses[$offset]['size']))
		return -1;
	return $offset;
}
function decimal_to_function($input) {
	global $addresses;
	if (!class_exists('core'))
		return '';
	return (isset($addresses[$input]['name']) && ($addresses[$input]['name'] != "")) ? $addresses[$input]['name'] : sprintf(core::addressformat, $input);
}
function debugvar($var, $label) {
	if ($GLOBALS['settings']['debug'])
		display::debugvar($var, $label);
}
function debugmessage($msg, $level = 'error') {
	if ($GLOBALS['settings']['debug'])
		display::debugmessage($msg,$level);
}
?>
