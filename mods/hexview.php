<?php
class hexview {
	private $main;
	
	function __construct(&$main) {
		$this->main = $main;
	}
	public function execute() {
		if (!isset($this->main->addresses[$this->main->offset]['size']))
			die('Data has no size defined!');
		require_once 'hexview.php';
		fseek($this->main->gamehandle, $this->main->platform->map_rom($this->main->offset));
		$data = fread($this->main->gamehandle, $this->main->addresses[$this->main->offset]['size']);
		$this->main->nextoffset = $this->main->decimal_to_function($this->main->offset+$this->main->addresses[$this->main->offset]['size']);
		if (isset($this->main->addresses[$this->main->offset]['charset']))
			$charset = $game['texttables'][$this->addresses[$this->offset]['charset']]['replacements'];
		else if (isset($game['defaulttext']))
			$charset = $game['texttables'][$game['defaulttext']]['replacements'];
		else
			$charset = null;
		if (isset($this->main->addresses[$this->main->offset]['description']))
			$this->main->dataname = $this->main->addresses[$this->main->offset]['description'];
		return hexview($data, isset($this->main->addresses[$this->main->offset]['width']) ? $this->main->addresses[$this->main->offset]['width'] : 16, $this->main->offset, $charset);
	}
	public static function shouldhandle($main) {
		if (isset($main->addresses[$main->offset]['type']) && ($main->addresses[$main->offset]['type'] === 'data') && !isset($main->addresses[$main->offset]['entries']))
			return true;
		return false;
	}
}
?>