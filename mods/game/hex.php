<?php
class hex extends gamemod {
	private $offset;
	public function getTemplate() { return 'hex'; }
	public function execute($arg) {
		require 'libs/hexview.php';
		$b = explode('-', $arg);
		if (count($b) > 1) {
			$arg = $b[0];
			$size = $b[1] - $b[0];
		} else {
			if (!isset($this->addresses[$arg]['size']))
				die('Data has no size defined!');
			$size = $this->addresses[$arg]['size'];
		}
		$this->offset = $arg;
		if (isset($this->addresses[$arg]['charset']))
			$charset = $game['texttables'][$this->addresses[$arg]['charset']]['replacements'];
		else if (isset($game['defaulttext']))
			$charset = $game['texttables'][$game['defaulttext']]['replacements'];
		else
			$charset = null;
		if (isset($this->addresses[$arg]['filter_size']))
			$size = $this->addresses[$arg]['filter_size'];
		dprintf('reading %d bytes', $size);
		return hexview($this->source->getString($size), isset($this->addresses[$arg]['width']) ? $this->addresses[$arg]['width'] : 16, $arg, $charset);
	}
}
?>