<?php
if (isset($_GET['begin'])) {
	$options = '';
	foreach ($_GET as $key => $val)
		if (($key != 'begin') && ($key != 'game')) {
			if ($val == 'true')
				$options[] = $key;
			else if ($val != null)
				$options[] = sprintf('%s=%s', $key, $val);
		}
	header(sprintf('Location: http://%s/%s/%s/%s',$_SERVER['HTTP_HOST'],$_GET['game'], $_GET['begin'], implode('/', $options)));
	die();
}
class display extends singleton {
	static $instance;
	private $dwoo;
	public $mode;
	private $error = false;
	
	function __construct() {
		require_once 'Dwoo/dwooAutoload.php';
		$this->dwoo = new Dwoo();
	}
	public function getArgv() {
		return array_slice(explode('/', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI'])),1);
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
		Main::get()->debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->dwoo->output('templates/'.$this->mode.'.tpl',
		array(
		'routinename' => Main::get()->dataname, 
		'title' => Main::get()->game['title'], 
		'nextoffset' => Main::get()->nextoffset, 
		'game' => Main::get()->gameid, 
		'data' => $data, 
		'thisoffset' => Main::get()->offset, 
		'options' => Main::get()->opts, 
		'writemode' => Main::get()->godpowers,
		'offsetname' => Main::get()->decimal_to_function(Main::get()->offset), 
		'realname' => Main::get()->getOffsetName(Main::get()->offset),
		'realdesc' => Main::get()->realdesc,
		'size' => isset(Main::get()->opts['size']) ? Main::get()->opts['size'] : 0,
		'addrformat' => core::addressformat, 
		'menuitems' => Main::get()->menuitems, 
		'opcodeformat' => core::opcodeformat,
		'comments' => Main::get()->comments,
		'miscdata' => platform::get()->getMiscInfo(),
		'error' => $this->error,
		'gamelist' => Main::get()->gamelist));
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
		if (isset(Main::get()->opts['logout'])) {
			setcookie('pass', null, -1, '/', $_SERVER['SERVER_NAME']);
			return false;
		} else if (isset(Main::get()->opts['login']) && (hash('sha256', Main::get()->opts['login']) === Main::get()->settings['password'])) {
			setcookie('pass', Main::get()->settings['password'], pow(2,31)-1, '/', $_SERVER['SERVER_NAME']);
			return true;
		} else if (isset($_COOKIE['pass']) && ($_COOKIE['pass'] === Main::get()->settings['password']))
			return true;
		return false;
	}
}
?>