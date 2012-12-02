<?php
class display {
	private $twig;
	public $mode;
	public $displaydata = array();
	
	function __construct() {
		global $settings;
		require_once 'Twig/Autoloader.php';
		Twig_Autoloader::register();
		require_once 'peng/twigext.php';
		header('Content-Type: text/html; charset=utf-8');
		$loader = new Twig_Loader_Filesystem('templates');
		$this->twig = new Twig_Environment($loader, array('debug' => $settings['debug']));
		$this->twig->addExtension(new Twig_Extension_Debug());
		$this->twig->addExtension(new Penguin_Twig_Extensions());
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
	public function display($data) {
		global $miscoutput;
		debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->displaydata['data'] = $data;
		echo $this->twig->render($this->mode.'.tpl', $this->displaydata);
	}
	public static function display_error($error) {
		$twig = new Twig_Environment(new Twig_Loader_Filesystem('templates'), array('debug' => $settings['debug']));
		$twig->addExtension(new Twig_Extension_Debug());
		$twig->addExtension(new Penguin_Twig_Extensions());
		echo $this->twig->render('error.tpl', array('routinename' => '', 'hideright' => true, 'title' => 'FLAGRANT SYSTEM ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => '', 'error' => 1));
	}
	public function canWrite() {
		if (isset($_SESSION['username']) && in_array($_SESSION['username'], $GLOBALS['settings']['admins']))
			return true;
		return false;
	}
}
?>