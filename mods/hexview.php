<?php
class hexview {
	private $main;
	
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		if (!isset($this->main->addresses[$this->main->offset]['size']))
			die('Data has no size defined!');
		require_once 'hexview.php';
		$data = rom::get()->read($this->main->addresses[$this->main->offset]['size'], platform::get()->map_rom($this->main->offset));
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
	public static function shouldhandle() {
		if (isset(Main::get()->addresses[Main::get()->offset]['type']) && ((Main::get()->addresses[Main::get()->offset]['type'] === 'data') || (Main::get()->addresses[Main::get()->offset]['type'] === 'empty')) && !isset(Main::get()->addresses[Main::get()->offset]['entries']))
			return true;
		return false;
	}
}
?>