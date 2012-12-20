<?php
//register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
//set_exception_handler('print_exception');
//set_error_handler('error_handling');
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
			debugmessage(sprintf("%s on %s:%d", $message, $file, $line));
	}
	return true;
}
function flagrant_system_error() {
	if (($error = error_get_last()) !== null) {
		if ($error['type'] == 1) {
			if (PHP_SAPI == 'cli')
				printf('SERIOUS ERROR: %s in %s:%d', $error['message'], $error['file'], $error['line']);
			else
				display_error(array('trace' => debug_backtrace(), 'message' => $error['message']));
		}
	} else {
		ob_end_flush();
	}
}
function display_error($error) {
	$twig = new Twig_Environment(new Twig_Loader_Filesystem('templates'), array('debug' => $settings['debug']));
	$twig->addExtension(new Twig_Extension_Debug());
	$twig->addExtension(new Penguin_Twig_Extensions());
	echo $this->twig->render('error.tpl', array('routinename' => '', 'hideright' => true, 'title' => 'FLAGRANT SYSTEM ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => '', 'error' => 1));
}
function hexafixer($matches) {
	//if ($matches[0][0] === ' ')
	//	return sprintf(' 0x%04X:', $matches[1]);
	return sprintf('0x%06X:', $matches[1]);
}
function json($obj) {
	return json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG);
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
interface filter_interface {
	public function getByte();
	public function getShort();
	public function getLong();
	public function isInRange($offset);
}
abstract class filter implements filter_interface {
	protected $dataSource;
	public function setDataSource(filter $source) { $this->dataSource = $source; }
	public function isInRange($offset) { return $this->dataSource->isInRange($offset); }
	public function getByte() { return $this->dataSource->getByte(); }
	public function getShort() { return $this->dataSource->getShort(); }
	public function getLong() { return $this->dataSource->getLong(); }
	public function seekTo($offset) { $this->dataSource->seekTo($offset); }
	public function identifyArea($offset) { return 'data'; }
	public function currentOffset() { return $this->dataSource->currentOffset(); }
}
interface seekable {
	public function seekTo($offset);
}
abstract class platform extends filter implements seekable {
	protected $lastSource = 'rom';
	protected $offset = 0;
	public function setDataSource(filter $source, $where = 'rom') { $this->dataSource[$where] = $source; }
	public function getMiscInfo() {
		return array();
	}
	public function seekTo($offset) {
		list($source, $trueOffset) = $this->map($offset);
		$this->lastSource = $source;
		$this->dataSource[$source]->seekTo($trueOffset);
		$this->offset = $offset;
	}
	public function isInRange($offset) { return true; }
	public function getByte() { $this->offset++; return $this->dataSource[$this->lastSource]->getByte(); }
	public function getShort() { $this->offset += 2; return $this->dataSource[$this->lastSource]->getShort(); }
	public function getLong() { $this->offset += 4; return $this->dataSource[$this->lastSource]->getLong();	}
	public function getVar($size) { 
		$output = 0;
		for ($i = 0; $i < $size; $i++)
			$output += ($this->getByte())<<($i*8);
		return $output;
	}
	public function currentOffset() { return $this->offset; }
	public function identifyArea($offset) { return $this->map($offset)[0]; }
}
class rawData extends filter {
	private $handle;
	public function isInRange($offset) {
		$curoffset = ftell($this->handle);
		fseek($this->handle, 0, SEEK_END);
		$size = ftell($this->handle);
		fseek($this->handle, $curoffset, SEEK_SET);
		return $offset <= $size;
	}
	
	public function getByte() { return $this->read_varint(1); }
	public function getShort() { return $this->read_varint(2); }
	public function getLong() { return $this->read_varint(4); }
	public function getString($size) { return fread($this->handle, $size); }
	public function seekTo($offset) { fseek($this->handle, $offset); }
	public function open($file) { 
		if (!file_exists($file))
			throw new Exception('Could not find file');
		$this->handle = fopen($file, 'r');
	}
	public function currentOffset() { return ftell($this->handle); }
	
	public function read_varint($size, $offset = -1, $endianness = null) {
		if ($offset > 0)
			$this->seekTo($offset);
		$output = 0;
		if ($endianness == 'l')
			for ($i = 0; $i < $size; $i++)
				$output += ord(fgetc($this->handle))<<(($size-$i-1)*8);
		else if ($endianness == 'm') {
			$output += ord(fgetc($this->handle))<<(2*8);
			$output += ord(fgetc($this->handle))<<(0*8);
			$output += ord(fgetc($this->handle))<<(1*8);
		}
		else
			for ($i = 0; $i < $size; $i++)
				$output += ord(fgetc($this->handle))<<($i*8);
		return $output;
	}
}
function defaultv($format) {
	if (isset($format))
		return $format;
	return '%s';
}
abstract class gamemod {
	const title = '';
	protected $addresses;
	protected $platform;
	protected $game;
	protected $metadata = array();
	
	public function getMetadata() {
		return $this->metadata;
	}
	public function getDescription() {
		return $this::title;
	}
	public function setAddresses($addr) {
		$this->addresses = $addr;
	}
	public function setDataSource($platform) {
		$this->platform = $platform;
	}
	public function setGameData($data) {
		$this->game = $data;
	}
	public function getTemplate() {
		return $this::title;
	}
}
interface table_data {
	public function __construct(filter $source, $gamedetails, $values);
	public function getValue();
}
abstract class cpucore {
	protected $initialoffset;
	protected $currentoffset;
	protected $branches = array();
	protected $dataSource;
	protected $platform;
	
	public static function getTemplate() { return 'assembly'; }
	public static function addressFormat() { return '%X'; }
	public static function opcodeFormat() { return '%02X'; }
	public static function getOptions() { return array(); }
	public function getOpcodes() { return array(); }
	public function getCurrentOffset() { return $this->currentoffset; }
	public function getInitialOffset() { return $this->initialoffset; }
	public function getBranches() { ksort($this->branches); return $this->branches; }
	public function getDefault() { }
	public function setPlatform($src) { $this->dataSource = $src; $this->platform = $src; }
	//public function setPlatform($platform) { $this->platform = $platform; }
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
	return '';
	//return sprintf(core::addressformat, $offset);
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
	if (!$GLOBALS['settings']['debug'])
		return;
	static $limit = 100;
	if (headers_sent())
		return;
	if ($limit-- > 0)
		ChromePhp::log($label, $var);
}
function debugmessage($message, $level = 'error') {
	if (!$GLOBALS['settings']['debug'])
		return;
	if (headers_sent())
		return;
	static $limit = 100;
	if ($limit-- > 0) {
		if ($level === 'error')
			ChromePhp::error($message);
		else if ($level === 'warn')
			ChromePhp::warn($message);
		else
			ChromePhp::log($message);
	}
}
?>
