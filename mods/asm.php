<?php
class asm {
	private $main;
	
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$output = core::get()->execute($this->main->offset);
		$this->main->nextoffset = $this->main->decimal_to_function(core::get()->currentoffset);

		if (isset($this->main->addresses[core::get()->initialoffset]['description']))
			$this->main->dataname = $this->main->addresses[core::get()->initialoffset]['description'];
		else
			$this->main->dataname = sprintf('%X', core::get()->initialoffset);
			
		if (isset($this->main->addresses[core::get()->initialoffset]['arguments']))
			$this->main->comments = $this->main->addresses[core::get()->initialoffset]['arguments'];
			
		if (isset($this->main->addresses[core::get()->initialoffset]['labels']))
			foreach ($this->main->addresses[core::get()->initialoffset]['labels'] as $branch)
				$this->main->menuitems[$branch] = $branch;
		else if (isset(core::get()->branches))
			foreach (core::get()->branches as $branch)
				$this->main->menuitems[$branch] = $branch;
		if (isset($this->main->opts['write']) && ($this->main->godpowers))
			$this->saveData();
		return array($output);
	}
	//Saves a stub to the relevant YAML file.
	private function saveData() {
		$branches = null;
		list($gameorig,$addresses) = $this->main->loadYAML($this->main->gameid);
		if (isset($addresses[core::get()->initialoffset]['labels']))
			$branches = $addresses[core::get()->initialoffset]['labels'];
		if (core::get()->branches !== null) {
			ksort(core::get()->branches);
			$unknownbranches = 0;
			foreach (core::get()->branches as $k=>$branch)
				$branches[$k] = 'UNKNOWN'.$unknownbranches++;
		}
		if (!isset($addresses[core::get()->initialoffset]['name']) && (isset($this->main->opts['name'])))
			$addresses[core::get()->initialoffset]['name'] = $this->main->opts['name'];
		if (!isset($addresses[core::get()->initialoffset]['description']) && (isset($this->main->opts['desc'])))
			$addresses[core::get()->initialoffset]['description'] = $this->main->opts['desc'];
		$addresses[core::get()->initialoffset]['type'] = 'assembly';
		$addresses[core::get()->initialoffset]['size'] = core::get()->currentoffset-core::get()->initialoffset;
		foreach (core::get()->getMisc() as $k=>$val)
			$addresses[core::get()->initialoffset][$k] = $val;
		if ($branches != null)
			$addresses[core::get()->initialoffset]['labels'] = $branches;
		ksort($addresses);
		$output = preg_replace_callback('/^(\d+):/m', 'hexafixer', yaml_emit($gameorig,YAML_UTF8_ENCODING).yaml_emit($addresses,YAML_UTF8_ENCODING));
		file_put_contents('games/'.$this->main->gameid.'.yml', $output);
	}
	public static function shouldhandle() {
		if (!isset(Main::get()->addresses[Main::get()->offset]['type']) || (Main::get()->addresses[Main::get()->offset]['type'] !== 'data'))
			return true;
		return false;
	}
	public function getTemplate() {
		return core::template;
	}
}
?>