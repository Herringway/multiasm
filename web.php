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
	header(sprintf('Location: http://%s/%s/%s/%s',$_SERVER['SERVER_NAME'],$_GET['game'], $_GET['begin'], implode('/', $options)));
	die();
}
class display {
	private $main;
	private $dwoo;
	public $mode;
	
	function __construct(&$main) {
		$this->main = $main;
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
	public function display($data) {
		global $miscoutput;
		$this->main->debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->dwoo->output('templates/'.$this->mode.'.tpl',
		array(
		'routinename' => $this->main->dataname, 
		'title' => $this->main->game['title'], 
		'nextoffset' => $this->main->nextoffset, 
		'game' => $this->main->gameid, 
		'data' => $data, 
		'thisoffset' => $this->main->offset, 
		'options' => $this->main->opts, 
		'writemode' => $this->main->godpowers,
		'offsetname' => $this->main->offsetname, 
		'realname' => $this->main->realname,
		'realdesc' => $this->main->realdesc,
		'addrformat' => core::addressformat, 
		'menuitems' => $this->main->menuitems, 
		'opcodeformat' => core::opcodeformat,
		'comments' => $this->main->comments,
		'miscdata' => $this->main->platform->getMiscInfo(),
		'gamelist' => $this->main->gamelist));
	}
	public static function display_error($error) {
		$dwoo = new Dwoo();
		$dwoo->output('/etc/nginx/html/disasm/templates/error.tpl', array('routinename' => '', 'title' => 'FLAGRANT SYSTEM ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => '', 'error' => 1));
	}
	public static function debugvar($var, $label) {
		ChromePhp::log($label, $var);
	}
	public static function debugmessage($message) {
		ChromePhp::error($message);
	}
	public function canWrite() {
		if (isset($this->main->opts['logout'])) {
			setcookie('pass', null, -1, '/', $_SERVER['SERVER_NAME']);
			return false;
		} else if (isset($this->main->opts['login']) && (hash('sha256', $this->main->opts['login']) === $this->main->settings['password'])) {
			setcookie('pass', $this->main->settings['password'], pow(2,31)-1, '/', $_SERVER['SERVER_NAME']);
			return true;
		} else if ($_COOKIE['pass'] === $this->main->settings['password'])
			return true;
		return false;
	}
}
?>