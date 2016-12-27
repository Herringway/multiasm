<?php
require_once 'libs/exceptions.php';
require_once 'vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\FirePHPHandler;
$log = new Logger('MPASM');
$log->pushHandler(new StreamHandler('error.log', Logger::WARNING));
$log->pushHandler(new StreamHandler('debug.log', Logger::DEBUG));
$log->pushHandler(new FirePHPHandler(Logger::DEBUG));
$log->pushHandler(new ChromePHPHandler(Logger::DEBUG));
date_default_timezone_set('America/Halifax');
set_error_handler('error_handling');
ini_set('yaml.output_width', -1);
define('BRANCH_LIMIT', 5000);
function error_handling($errno, $message, $file, $line, $context) {
	static $errors = 0;
	if ($errors++ < (isset($GLOBALS['settings']['errorlimit']) ? $GLOBALS['settings']['errorlimit'] : 50))
		$GLOBALS['ERRORS'][] = sprintf("%s on %s:%d", $message, $file, $line);
	return true;
}
function uint($i, $bits) {
	return $i < pow(2,$bits-1) ? $i : 0-(pow(2,$bits)-$i);
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
	protected $selectedSource = 'rom';
	protected $offset = 0;
	protected $dataSource = array();
	protected $firstseek = true;
	public function setDataSource(filter $source, $where = 'rom') { $this->dataSource[$where] = $source; }
	public function getSources() { return array_keys($this->dataSource); }
	public function getMiscInfo() {
		return array();
	}
	public function getPlatformAddresses() { return array(); }
	public function seekTo($offset) {
		$this->firstseek = false;
		try {
		list($this->selectedSource, $trueOffset) = $this->map($offset);
		debugvar($this->selectedSource, sprintf('seeking to %s in ', $offset));
		if (!isset($this->dataSource[$this->selectedSource]))
			$this->selectedSource = 'noise';
		} catch (Exception $e) {
			$this->selectedSource = 'noise';
		}
		$this->dataSource[$this->selectedSource]->seekTo($trueOffset);
		$this->offset = $offset;
	}
	public function seekRel($offset) {
		$this->seekTo($this->offset + $offset);
	}
	public function isInRange($offset) {
		try {
			list($source, $trueOffset) = $this->map($offset);
			debugvar($trueOffset, 'true offset');
			return $this->dataSource[$source]->isInRange($trueOffset);
		} catch (Exception $e) {
			dprintf('caught exception: %s', $e->getMessage());
		}
		return false;
	}
	public function getByte() { $tmp = $this->dataSource[$this->selectedSource]->getByte(); $this->seekRel(1); return $tmp; }
	public function getShort() { $tmp = $this->dataSource[$this->selectedSource]->getShort(); $this->seekRel(2); return $tmp; }
	public function getLong() { $tmp = $this->dataSource[$this->selectedSource]->getLong(); $this->seekRel(4); return $tmp; }
	public function getString($size) { $tmp = $this->dataSource[$this->selectedSource]->getString($size); $this->seekRel($size); return $tmp; }
	public function init() {
		$this->setDataSource(new noiseSource(), 'noise');
	}
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
		return ($this->getShort()<<16) + $this->getShort();
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
		return $offset <= $this->size;
	}
	public function getSize() { return $this->size; }
	public function getByte() { return $this->getVar(1); }
	public function getShort() { return $this->getVar(2); }
	public function getLong() { return $this->getVar(4); }
	public function getString($size) { return fread($this->handle, $size); }
	public function seekTo($offset) { fseek($this->handle, $offset); }
	public function open($file) { 
		if (!file_exists($file))
			throw new FileNotFoundException($file);
		$this->handle = fopen($file, 'r');
		fseek($this->handle, 0, SEEK_END);
		$this->size = ftell($this->handle);
		fseek($this->handle, 0, SEEK_SET);
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
class noiseSource extends filter {
	
	public function isInRange($offset) {
		return true;
	}
	public function getSize() { return 0; }
	public function getByte() { return $this->getVar(1); }
	public function getShort() { return $this->getVar(2); }
	public function getLong() { return $this->getVar(4); }
	public function getString($size) { $data = ''; while ($size--) $data .= chr($this->getVar(1)); return $data; }
	public function seekTo($offset) { }
	public function currentOffset() { return 0; }
	
	public function getVar($size, $endianness = null) {
		return mt_rand(0, pow(2, $size*8)-1);
	}
}
class memoryData extends filter {
	private $data;
	private $position = 0;
	public function isInRange($offset) {
		return $offset < count($this->data);
	}
	public function setData(array $data) {
		$this->data = $data;
	}
	public function getByte() {
		return $this->data[$this->position++];
	}
	public function getShort() {
		return $this->getByte() + ($this->getByte()<<8);
	}
	public function getLong() {
		return $this->getShort() + ($this->getShort()<<16);
	}
	public function seekTo($target) {
		$this->position = $target;
	}
	public function currentOffset() {
		return $this->position;
	}
	public function getVar($size, $endianness = null) {
		$output = 0;
		if ($endianness == 'l')
			for ($i = 0; $i < $size; $i++)
				$output += $this->getByte()<<(($size-$i-1)*8);
		else if ($endianness == 'm') {
			$output += $this->getByte()<<(2*8);
			$output += $this->getByte()<<(0*8);
			$output += $this->getByte()<<(1*8);
		}
		else
			for ($i = 0; $i < $size; $i++)
				$output += $this->getByte()<<($i*8);
		return $output;
	}
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
abstract class cpucore {
	protected $initialoffset;
	protected $currentoffset;
	protected $branches = array();
	protected $dataSource;
	protected $platform;
	protected $opcodes = array();
	protected $breakpoints = array();
	protected $lastOpcode;
	protected $processorState;
	
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
	public function setBreakPoint($addr, $label = '') { debugvar($addr, sprintf('adding %s breakpoint',$label)); $this->breakpoints[$label] = $addr; }
	public function setState($flag, $state) { $this->processorState[$flag] = $state; }
	public function getState($flag) { return $this->processorState[$flag]; }
	protected function initializeProcessor() { }
	protected function setup($addr) { }
	protected function executeInstruction($instruction) { }
	public function execute($addr) {
		$thisentry = AddressFactory::getAddressEntryFromOffset($addr);
		$this->initializeProcessor();
		$this->initialoffset = $this->currentoffset = $addr;
		$this->setup($addr);
		if (isset($thisentry['Initial State']))
			foreach ($thisentry['Initial State'] as $flag=>$state)
				$this->setState($flag, $state);
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
				$this->setBreakPoint($this->currentoffset, $e->getMessage());
			}
			$this->executeInstruction($instruction);
		}
		return $output;
	}
}
function gametitle($game) {
	$miscdata = array();
	if (!isset($game['Title']))
		throw new InvalidArgumentException('No title available');
	if (isset($game['Country']))
		$miscdata[] = $game['Country'];
	if (isset($game['Version']))
		$miscdata[] = 'v'.$game['Version'];
	return $game['Title'].(($miscdata != array()) ? ' ('.implode(' ', $miscdata).')' : '');
}
function debugvar($var, $label) {
	if (!$GLOBALS['settings']['debug'])
		return;
	global $log;
	debugmessage($label.' '.var_export($var, true));
}
function debugmessage($message, $level = 'error') {
	if (!$GLOBALS['settings']['debug'])
		return;
	static $count = 0;
	if ($count++ > 100)
		return;
	global $log;
	if ($level === 'error')
		$log->error($message);
	else if ($level === 'warn')
		$log->warning($message);
	else
		$log->debug($message);
}
function dprintf($message) {
	$args = func_get_args();
	array_shift($args);
	debugmessage(vsprintf($message, $args), 'info');
}
?>
