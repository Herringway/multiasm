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
			
		//Saves a stub to the relevant YAML file. Work on this later.
		/*if (isset($game['genstub'])) {
			$branches = null;
			if (isset($known_addresses_p[$core->initialoffset]['labels']))
				$branches = $known_addresses_p[$core->initialoffset]['labels'];
			if ($core->branches !== null) {
				ksort($core->branches);
				$unknownbranches = 0;
				foreach ($core->branches as $k=>$branch)
					$branches[$k] = 'UNKNOWN'.$unknownbranches++;
			}
			if (!isset($known_addresses_p[$core->initialoffset]['name']))
				$known_addresses_p[$core->initialoffset]['name'] = '';
			if (!isset($known_addresses_p[$core->initialoffset]['description']))
				$known_addresses_p[$core->initialoffset]['description'] = '';
			$known_addresses_p[$core->initialoffset]['type'] = 'assembly';
			$known_addresses_p[$core->initialoffset]['size'] = $core->currentoffset-$core->initialoffset;
			foreach ($core->getMisc() as $k=>$val)
				$known_addresses_p[$core->initialoffset][$k] = $val;
			$known_addresses_p[$core->initialoffset]['labels'] = $branches;
			ksort($known_addresses_p);
			$output = preg_replace_callback('/ ?(\d+):/', 'hexafixer', yaml_emit($gameorig).yaml_emit($known_addresses_p));
			file_put_contents('games/'.$settings['gameid'].'.yml', $output);
		}*/
		$this->main->yamldata[] = $output;
		return $output;
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