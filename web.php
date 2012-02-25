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
	public $dataname;
	
	function __construct(&$main) {
		$this->main = $main;
		require_once 'Dwoo/dwooAutoload.php';
		$this->dwoo = new Dwoo();
	}
	public function getArgv() {
		return array_slice(explode('/', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI'])),1);
	}
	public function display($data) {
		$this->main->debugvar($this->mode, 'displaymode');
		header('Content-Type: text/html; charset=UTF-8');
		$this->dwoo->output('templates/'.$this->mode.'.tpl', array('routinename' => $this->dataname, 'title' => $this->main->game['title'], 'nextoffset' => $this->main->nextoffset, 'game' => $this->main->gameid, 'data' => $data, 'thisoffset' => $this->main->offset, 'options' => $this->main->opts, 'offsetname' => $this->main->offsetname, 'addrformat' => core::addressformat, 'branches' => $this->main->core->branches, 'opcodeformat' => core::opcodeformat));
	}
}
?>