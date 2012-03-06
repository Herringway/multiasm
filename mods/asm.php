<?php
class asm {
	private $main;
	
	function __construct(&$main) {
		$this->main = $main;
	}
	public function execute() {
		$output = $this->main->core->execute($this->main->offset);
		$this->main->nextoffset = $this->main->decimal_to_function($this->main->core->currentoffset);

		if (isset($this->main->addresses[$this->main->core->initialoffset]['description']))
			$this->main->dataname = $this->main->addresses[$this->main->core->initialoffset]['description'];
		else
			$this->main->dataname = sprintf('%X', $this->main->core->initialoffset);
			
		if (isset($this->main->addresses[$this->main->core->initialoffset]['arguments']))
			$this->main->comments = $this->main->addresses[$this->main->core->initialoffset]['arguments'];
			
		if (isset($this->main->addresses[$this->main->core->initialoffset]['labels']))
			foreach ($this->main->addresses[$this->main->core->initialoffset]['labels'] as $branch)
				$this->main->menuitems[$branch] = $branch;
		else if (isset($this->main->core->branches))
			foreach ($this->main->core->branches as $branch)
				$this->main->menuitems[$branch] = $branch;
		if (isset($this->main->opts['write']) && ($this->main->godpowers))
			$this->saveData();
		$this->main->yamldata[] = $output;
		return $output;
	}
	//Saves a stub to the relevant YAML file.
	private function saveData() {
		$branches = null;
		list($gameorig,$addresses) = $this->main->loadYAML($this->main->gameid);
		if (isset($addresses[$this->main->core->initialoffset]['labels']))
			$branches = $addresses[$this->main->core->initialoffset]['labels'];
		if ($this->main->core->branches !== null) {
			ksort($this->main->core->branches);
			$unknownbranches = 0;
			foreach ($this->main->core->branches as $k=>$branch)
				$branches[$k] = 'UNKNOWN'.$unknownbranches++;
		}
		if (!isset($addresses[$this->main->core->initialoffset]['name']) && (isset($this->main->opts['name'])))
			$addresses[$this->main->core->initialoffset]['name'] = $this->main->opts['name'];
		if (!isset($addresses[$this->main->core->initialoffset]['description']) && (isset($this->main->opts['desc'])))
			$addresses[$this->main->core->initialoffset]['description'] = $this->main->opts['desc'];
		$addresses[$this->main->core->initialoffset]['type'] = 'assembly';
		$addresses[$this->main->core->initialoffset]['size'] = $this->main->core->currentoffset-$this->main->core->initialoffset;
		foreach ($this->main->core->getMisc() as $k=>$val)
			$addresses[$this->main->core->initialoffset][$k] = $val;
		if ($branches != null)
			$addresses[$this->main->core->initialoffset]['labels'] = $branches;
		ksort($addresses);
		$output = preg_replace_callback('/ ?(\d+):/', 'hexafixer', yaml_emit($gameorig).yaml_emit($addresses));
		file_put_contents('games/'.$this->main->gameid.'.yml', $output);
	}
	public static function shouldhandle($main) {
		if (!isset($main->addresses[$main->offset]['type']) || ($main->addresses[$main->offset]['type'] !== 'data'))
			return true;
		return false;
	}
	public function getTemplate() {
		return core::template;
	}
}
?>