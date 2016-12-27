<?php
class hex extends gamemod {
	private $offset;
	public function getTemplate() { return 'hex'; }
	public function execute($arg) {
		require_once 'src/hexview.php';
		if (!isset($this->address['Size']))
			die('Data has no size defined!');
		$size = $this->address['Size'];
		$this->offset = $arg;
		if (isset($this->address['charset']))
			$charset = $this->game['scripttables'][$this->address['charset']]['replacements'];
		else if (isset($this->game['defaultscript']))
			$charset = $this->game['scripttables'][$this->game['defaultscript']]['replacements'];
		else
			$charset = null;
		if ($charset !== null) {
			foreach ($charset as $k=>$char)
				if (is_array($char))
					unset($charset[$k]);
		}
		if (isset($this->address['filter_size']))
			$size = $this->address['filter_size'];
		dprintf('reading %d bytes', $size);
		return hexview($this->source->getString($size), isset($this->address['Width']) ? $this->address['Width'] : 16, $arg, $charset);
	}
}
?>
