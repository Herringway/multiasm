<?php
require 'libs/lightopenid/openid.php';
$openid = new LightOpenID($_SERVER['SERVER_NAME']);
if ($openid->validate()) {
	session_start();
	$attr = $openid->getAttributes();
	$splitmail = explode('@', $attr['contact/email']);
	$_SESSION['username'] = $splitmail[0];
	$split = explode('=', $openid->identity);
	$_SESSION['id'] = $split[1];
	header(sprintf('Location: http://%s/',$_SERVER['SERVER_NAME']));
	die();
}
if (count($_GET) > 0) {
	$options = '';
	if (isset($_GET['coremod']))
		$options[] = $_GET['coremod'];
	if (isset($_GET['param']))
		$options[] = $_GET['param'];
	foreach ($_GET as $key => $val)
		if (($key != 'param') && ($key != 'coremod')) {
			if ($val == 'true')
				$options[] = $key;
			else if ($val != null)
				$options[] = sprintf('%s=%s', urlencode($key), urlencode($val));
		}
	header(sprintf('Location: http://%s/%s/',$_SERVER['HTTP_HOST'], implode('/', $options)));
	die();
}
session_start();
require_once 'libs/chromephp/ChromePhp.php';
debugvar($_SESSION, 'session info');
class display {
	private $dwoo;
	public $mode;
	private $error = false;
	public $displaydata = array();
	
	function __construct() {
		require_once 'Dwoo/dwooAutoload.php';
		$this->dwoo = new Dwoo();
	}
	public function getArgv() {
		$uristring = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
		$args = array_slice(explode('/', $uristring),1);
		if (strstr($args[count($args)-1], '.') !== FALSE)
			$args[count($args)-1] = strstr($args[count($args)-1], '.', true);
		return $args;
	}
	public function getFormat() {
		static $types = array();
		if (function_exists('yaml_emit')) {
			$types['yml'] = 'yml';
			$types['yaml'] = 'yml';
		}
		if (function_exists('json_encode'))
			$types['json'] = 'json';
		$v = substr(strrchr($_SERVER['REQUEST_URI'], '.'),1);
		if (!isset($types[$v]))
			return 'html';
		return $types[$v];
	}
	public function getOpts($argv) {
		$opts = array();
		for ($i = 2; $i < count($argv); $i++) {
			$v = explode('=', $argv[$i]);
			if (isset($v[1]))
				$opts[$v[0]] = $v[1];
			else
				$opts[$v[0]] = true;
		}
		return $opts;
	}
	public function setError() {
		$this->error = true;
	}
	public function display($data) {
		global $miscoutput;
		debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->displaydata['data'] = $data;
		$this->dwoo->output('templates/'.$this->mode.'.tpl', $this->displaydata);
		/*
		array('title' => $GLOBALS['game']['fulltitle'], 'routinename' => $GLOBALS['dataname'])
		
		'nextoffset' => $GLOBALS['nextoffset'], 
		'game' => $GLOBALS['gameid'], 
		'thisoffset' => $GLOBALS['offset'], 
		'options' => $GLOBALS['opts'], 
		'writemode' => $GLOBALS['godpowers'],
		'offsetname' => decimal_to_function($GLOBALS['offset']), 
		'realname' => getOffsetName($GLOBALS['offset'], true),
		'realdesc' => $GLOBALS['realdesc'],
		'size' => isset($GLOBALS['opts']['size']) ? $GLOBALS['opts']['size'] : '',
		'addrformat' => core::addressformat, 
		'menuitems' => $GLOBALS['menuitems'], 
		'opcodeformat' => core::opcodeformat,
		'comments' => $GLOBALS['comments'],
		'miscdata' => $GLOBALS['platform']->getMiscInfo(),
		'error' => $this->error,
		'gamelist' => $GLOBALS['gamelist'])
		*/
	}
	public static function display_error($error) {
		$dwoo = new Dwoo();
		$dwoo->output('./templates/error.tpl', array('routinename' => '', 'hideright' => true, 'title' => 'FLAGRANT SYSTEM ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => '', 'error' => 1));
	}
	public static function debugvar($var, $label) {
		static $limit = 100;
		if ($limit-- > 0)
			ChromePhp::log($label, $var);
	}
	public static function debugmessage($message, $level = 'error') {
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
	public function canWrite() {
		if (isset($_SESSION['username']) && in_array($_SESSION['username'], $GLOBALS['settings']['admins']))
			return true;
		return false;
	}
}
?>