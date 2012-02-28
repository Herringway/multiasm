<?php
if (isset($_GET['begin'])) {
	$options = '';
	foreach ($_GET as $key => $val)
		if (($key != 'begin') && ($key != 'game')) {
			if ($val == 'true')
				$options[] = $key;
			else
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
		$this->main->debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->dwoo->output('templates/'.$this->mode.'.tpl', array('routinename' => $this->main->dataname, 'title' => $this->main->game['title'], 'nextoffset' => $this->main->nextoffset, 'game' => $this->main->gameid, 'data' => $data, 'thisoffset' => $this->main->offset, 'options' => $this->main->opts, 'offsetname' => $this->main->offsetname, 'addrformat' => core::addressformat, 'menuitems' => $this->main->menuitems, 'opcodeformat' => core::opcodeformat, 'gamelist' => $this->main->gamelist));
	}
	public static function display_error($error) {
		$dwoo = new Dwoo();
		$dwoo->output('templates/error.tpl', array('routinename' => '', 'title' => 'SERIOUS ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => ''));
	}
	public static function debugvar($var, $label) {
		ChromePhp::log($label, $var);
	}
}
?>