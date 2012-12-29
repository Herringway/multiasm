<?php
class table extends gamemod {
	private $offset;
	private $pointerblocks = array();
	public function getTemplate() { return 'table'; }
	public function execute($arg) {
		$this->offset = $arg;
		
		require_once 'mods/game/table/basetypes.php';
		$tablemod = new table_table($this->source, $this->game, $this->addresses[$this->offset]);
		//$tablemod->useOffsetKeys(true);
		$entries = $tablemod->getValue();
		
		$i = 0;
		$branchformat = 'UNKNOWN%0'.ceil(log(count($entries),10)).'d';
		foreach ($entries as $key => $branch) {
			if (isset($branch['Name']) && (trim($branch['Name']) != ''))
				$label = $branch['Name'];
			else
				$label = sprintf($branchformat, $i++);
			if (isset($this->addresses[$this->offset]['labels'][$this->offset - $key]))
				$label = $this->addresses[$this->offset]['labels'][$this->offset - $key];
			$this->metadata['menuitems'][$key] = $label;
		}
		return array($this->addresses[$this->offset]['entries'], $entries);
	}
}
?>