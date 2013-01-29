<?php
class table extends gamemod {
	private $offset;
	private $pointerblocks = array();
	public function getTemplate() { return 'table'; }
	public function execute($arg, $query = '') {
		$this->offset = $arg;
		
		require_once 'mods/game/table/basetypes.php';
		$tablemod = new table_struct($this->source, $this->game, $this->address);
		$this->metadata['offsetkeys'] = true;
		debugvar($this->metadata['options'], 'metadata');
		$tablemod->setMetadata($this->metadata);
		$entries = $tablemod->getValue();
		
		$i = 0;
		$branchformat = 'UNKNOWN%0'.ceil(log(count($entries),10)).'d';
		$cpucore = cpuFactory::getCPU($this->game['Processor']);
		foreach ($entries as $key => $branch) {
			if (isset($this->address['Labels'][$this->offset - $key]))
				$label = $this->address['Labels'][$this->offset - $key];
			else if (isset($branch['Name']) && (trim($branch['Name']) != ''))
				$label = $branch['Name'];
			else
				$label = sprintf($branchformat, $i++);
			$this->metadata['menuitems'][sprintf($cpucore->addressformat(), $key)] = $label;
		}
		/*$entries = array($this->address['Name'] => $entries);
		if (!empty($query)) {
			require_once 'libs/jsonpath-0.8.1.php';
			debugmessage($query, 'info');
			$entries = jsonPath($entries, $query);
		}*/
		//return array($this->address['entries'], $entries);
		return $entries;
	}
}
?>