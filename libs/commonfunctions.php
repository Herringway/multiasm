<?php
require_once 'evalmath.php';
//register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
//set_exception_handler('print_exception');
set_error_handler('error_handling');
ini_set('yaml.output_width', -1);
define('BRANCH_LIMIT', 5000);
function print_exception($exception) {
	$display->mode = 'error';
	$display->seterror();
	$display->display(array('trace' => $exception->getTrace(), 'message' => $exception->getMessage()));
}
function error_handling($errno, $message, $file, $line, $context) {
	static $errors = 0;
	if ($errors++ < (isset($GLOBALS['settings']['errorlimit']) ? $GLOBALS['settings']['errorlimit'] : 50))
		$GLOBALS['ERRORS'][] = sprintf("%s on %s:%d", $message, $file, $line);
	return true;
}
function flagrant_system_error() {
	if (($error = error_get_last()) !== null) {
		if ($error['type'] == 1)
			display_error(array('trace' => debug_backtrace(), 'message' => $error['message']));
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
	protected $options;
	public function __construct(filter $source = null) { $this->dataSource = $source; }
	public function setDataSource(filter $source) { $this->dataSource = $source; }
	public function isInRange($offset) { return $this->dataSource->isInRange($offset); }
	public function getByte() { return $this->dataSource->getByte(); }
	public function getShort() { return $this->dataSource->getShort(); }
	public function getLong() { return $this->dataSource->getLong(); }
	public function getString($size) { return $this->dataSource->getString($size); }
	public function getVar($size) { return $this->dataSource->getVar($size); }
	public function seekTo($offset) { $this->dataSource->seekTo($offset); }
	public function identifyArea($offset) { return 'data'; }
	public function currentOffset() { return $this->dataSource->currentOffset(); }
	public function setOption($option, $value) { $this->options[$option] = $value; }
	public function getSources() { return array('data'); }
}
interface seekable {
	public function seekTo($offset);
}
abstract class platform extends filter implements seekable {
	protected $lastSource = 'rom';
	protected $offset = 0;
	public function setDataSource(filter $source, $where = 'rom') { $this->dataSource[$where] = $source; }
	public function getSources() { return array_keys($this->dataSource); }
	public function getMiscInfo() {
		return array();
	}
	public function seekTo($offset) {
		list($source, $trueOffset) = $this->map($offset);
		debugvar($source, 'seeking into:');
		$this->lastSource = $source;
		$this->dataSource[$source]->seekTo($trueOffset);
		$this->offset = $offset;
	}
	public function isInRange($offset) { return true; }
	public function getByte() { $this->offset++; return $this->dataSource[$this->lastSource]->getByte(); }
	public function getShort() { $this->offset += 2; return $this->dataSource[$this->lastSource]->getShort(); }
	public function getLong() { $this->offset += 4; return $this->dataSource[$this->lastSource]->getLong();	}
	public function getString($size) { $this->offset += $size; return $this->dataSource[$this->lastSource]->getString($size);	}
	public function init() { }
	public function getVar($size) { 
		$output = 0;
		for ($i = 0; $i < $size; $i++)
			$output += ($this->getByte())<<($i*8);
		return $output;
	}
	public function currentOffset() { return $this->offset; }
	public function identifyArea($offset) { return $this->map($offset)[0]; }
}
abstract class compression_filter extends filter {
	protected $buffer;
	protected $location = 0;
	public function isInRange($offset) { return $this->dataSource->isInRange($offset); }
	public function getByte() {
		if (!isset($this->buffer[$this->location]))
			$this->decomp($this->location);
		return $this->buffer[$this->location++];
	}
	public function getShort() {
		return ($this->getByte()<<8) + $this->getByte();
	}
	public function getLong() {
		return ($this->getLong()<<16) + $this->getLong();
	}
	public function getString($size) {
		if (!isset($this->buffer[$this->location+$size]))
			$this->decomp($this->location+$size);
		$output = '';
		for ($i = 0; $i < $size; $i++)
			$output .= chr($this->buffer[$this->location+$i]);
		$this->location += $size;
		return $output;
	}
	public function seekTo($offset) { $this->location = $offset; }
	public function identifyArea($offset) { return 'comp_data'; }
	public function currentOffset() { return $this->location; }
	protected function decomp($offset) { }
}
class rawData extends filter {
	private $handle;
	private $size;
	public function isInRange($offset) {
		$this->getFileSize();
		return $offset <= $this->size;
	}
	private function getFileSize() {
		if (!isset($this->size)) {
			$curoffset = ftell($this->handle);
			fseek($this->handle, 0, SEEK_END);
			$this->size = ftell($this->handle);
			fseek($this->handle, $curoffset, SEEK_SET);
		}
	}
	public function getByte() { return $this->getVar(1); }
	public function getShort() { return $this->getVar(2); }
	public function getLong() { return $this->getVar(4); }
	public function getString($size) { return fread($this->handle, $size); }
	public function seekTo($offset) { fseek($this->handle, $offset); }
	public function open($file) { 
		if (!file_exists($file))
			throw new Exception('Could not find file');
		$this->handle = fopen($file, 'r');
	}
	public function currentOffset() { return ftell($this->handle); }
	
	public function getVar($size, $endianness = null) {
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
	protected $address;
	protected $source;
	protected $game;
	protected $metadata = array();
	public function getMetadata() {
		return $this->metadata;
	}
	public function getDescription() {
		return '';
	}
	public static function getMagicValue() { return null; }
	public static function getMenuEntries($source) { return array(); }
	public function init($arg) {
	
	}
	public function setMetadata(&$metadata) {
		$this->metadata = &$metadata;
	}
	public function setAddress($addr) {
		$this->address = $addr;
	}
	public function setDataSource($platform) {
		$this->source = $platform;
	}
	public function setGameData($data) {
		$this->game = $data;
	}
	public function getTemplate() {
		return $this::title;
	}
}
abstract class coremod {
	protected $metadata = array();

	public function setMetadata(&$metadata) {
		$this->metadata = &$metadata;
	}
	public function getMetadata() {
		return $this->metadata;
	}
}
interface __table_data {
	public function __construct(filter $source, $gamedetails, $values);
	public function getValue();
}
abstract class table_data implements __table_data {
	protected $source;
	protected $entry;
	protected $gamedetails;
	protected $metadata;
	protected static $math = null;
	public function __construct(filter $source, $gamedetails, $entry) {
		if (self::$math === null) 
			self::$math = new EvalMath();
		$this->source = $source;
		$this->details = $entry;
		$evalstr = $this->details['Size'];
		debugvar($evalstr, 'evaluating...');
		$result = self::$math->evaluate($evalstr);
		debugvar($result, 'newsize');
		$this->details['Size'] = $result;
		$this->gamedetails = $gamedetails;
	}
	public function setMetadata(&$input) {
		$this->metadata = &$input;
	}
	public function getValue() {
		$val = $this->__getValue();
		if (is_int($val)) {
			$set = sprintf('%s = %d', strtolower(str_replace(array('?', ' ', '"'), array('Q', '_', ''), $this->details['Name'])), $val);
			debugvar($set, 'setting');
			self::$math->evaluate($set);
		}
		return $val;
	}
}
abstract class cpucore {
	protected $initialoffset;
	protected $currentoffset;
	protected $branches = array();
	protected $dataSource;
	protected $platform;
	protected $opcodes = array();
	protected $breakpoints = array();
	protected $lastOpcode;
	protected $processorFlags;
	
	public static function getTemplate() { return 'assembly'; }
	public static function addressFormat() { return '%X'; }
	public static function opcodeFormat() { return '%02X'; }
	public static function getOptions() { return array(); }
	public function getOpcodes() { return $this->opcodes; }
	public function getCurrentOffset() { return $this->currentoffset; }
	public function getInitialOffset() { return $this->initialoffset; }
	public function getBranches() { ksort($this->branches); return $this->branches; }
	public function getDefault() { }
	public function setPlatform($src) { $this->dataSource = $src; $this->platform = $src; }
	public function setBreakPoint($addr) { $this->breakpoints[] = $addr; }
	public function setState($flag, $state) { $this->processorFlags[$flag] = $state; }
	protected function initializeProcessor() { }
	protected function setup($addr) { }
	protected function executeInstruction($instruction) { }
	public function execute($addr) {
		$thisentry = AddressFactory::getAddressEntryFromOffset($addr);
		$this->initializeProcessor();
		$this->initialoffset = $this->currentoffset = $addr;
		$this->setup($addr);
		$output = array();
		while (true) {
			foreach ($this->breakpoints as $breakpoint)
				if ($breakpoint <= $this->currentoffset) {
					debugmessage('Breaking at breakpoint');
					break 2;
				}
			$instruction = array();
			$instruction['offset'] = $this->currentoffset;
			try {
				$instruction = array_merge($instruction, $this->fetchInstruction());
				if (isset($instruction['destination'])) {
					if (!in_array($instruction['destination'], $this->branches)) {
						$jumpentry = AddressFactory::getAddressEntryFromOffset($instruction['destination']);
						if (isset($jumpentry['Final State'])) {
							foreach ($jumpentry['Final State'] as $flag=>$state)
								$this->setState($flag, $state);
						}
					}
				}
				if (isset($thisentry['Label States'][$this->currentoffset - $this->initialoffset]))
					foreach ($thisentry['Label States'][$this->currentoffset - $this->initialoffset] as $flag=>$state)
						$this->setState($flag, $state);
				$this->lastOpcode = $instruction['opcode'];
				$output[] = $instruction;
			} catch (Exception $e) {
				$this->setBreakPoint($this->currentoffset);
			}
			$this->executeInstruction($instruction);
		}
		return $output;
	}
	//public function setPlatform($platform) { $this->platform = $platform; }
}
function gametitle($game) {
	$miscdata = array();
	if (isset($game['Country']))
		$miscdata[] = $game['Country'];
	if (isset($game['Version']))
		$miscdata[] = 'v'.$game['Version'];
	return $game['Title'].(($miscdata != array()) ? ' ('.implode(' ', $miscdata).')' : '');
}
function debugvar($var, $label) {
	if (!$GLOBALS['settings']['debug'])
		return;
	static $limit = 200;
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
	static $limit = 200;
	if ($limit-- > 0) {
		if ($level === 'error')
			ChromePhp::error($message);
		else if ($level === 'warn')
			ChromePhp::warn($message);
		else
			ChromePhp::log($message);
	}
}
function dprintf($message) {
	$args = func_get_args();
	array_shift($args);
	debugmessage(vsprintf($message, $args), 'info');
}
?>
