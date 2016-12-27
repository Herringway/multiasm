<?php
class table extends gamemod {
	private $offset;
	private $pointerblocks = array();
	public function getTemplate() { 
		if (isset($this->address['Graph'])) 
			return 'graph';
		return 'table';
	}
	public function execute($arg, $query = '') {
		$this->offset = $arg;
		
		require_once 'src/mods/game/table/basetypes.php';
		$mod = 'table_'.$this->address['Type'];
		switch ($mod) {
			case 'table_data': $mod = 'table_struct'; break;
			default: break;
		}
		$tablemod = new $mod($this->source, $this->game, $this->address);
		if (!isset($this->address['Graph'])) 
			$this->metadata['offsetkeys'] = true;
		else
			$this->metadata['offsetkeys'] = false;
		$this->metadata['palette string'] = true;
		$this->metadata['Use Descriptions as Struct Names'] = true;
		debugvar($this->metadata['options'], 'metadata');
		$tablemod->setMetadata($this->metadata);
		$entries = $tablemod->getValue();
		
		$i = 0;
		$branchformat = 'UNKNOWN%0'.ceil(log(count($entries),10)).'d';
		$cpucore = cpuFactory::getCPU($this->game['Platform']);
		foreach ($entries as $key => $branch) {
			if (isset($this->address['Labels'][$this->offset - $key]))
				$label = $this->address['Labels'][$this->offset - $key];
			else if (isset($branch['Name']) && (trim($branch['Name']) != ''))
				$label = $branch['Name'];
			else
				$label = sprintf($branchformat, $i++);
			$this->metadata['menuitems'][sprintf($cpucore->addressformat(), $key)] = $label;
		}
		return $entries;
	}
}
?>
