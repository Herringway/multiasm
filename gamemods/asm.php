<?php
class asm extends gamemod {
	public function description() {
		global $offset;
		return getDescription($offset);
	}
	public function execute() {
		global $metadata, $core, $offset, $addresses, $godpowers, $opts;
		$output = $core->execute($offset);
		$metadata['nextoffset'] = decimal_to_function($core->currentoffset);
			
		if (isset($addresses[$offset]['arguments']))
			$metadata['comments'] = $addresses[$offset]['arguments'];
			
		if (isset($addresses[$offset]['labels']))
			foreach ($addresses[$offset]['labels'] as $branch)
				$metadata['menu'][$branch] = $branch;
		else if (isset($core->branches))
			foreach ($core->branches as $branch)
				$metadata['menu'][$branch] = $branch;
		if (isset($opts['write']) && ($godpowers))
			$this->saveData();
		return array($output);
	}
	//Saves a stub to the relevant YAML file.
	private function saveData() {
		global $core;
		debugmessage('Saving YAML', 'log');
		$branches = null;
		list($gameorig,$addresses) = $this->main->loadYAML($this->main->gameid);
		if (isset($addresses[$core->initialoffset]['labels']))
			$branches = $addresses[$core->initialoffset]['labels'];
		if ($core->branches !== null) {
			ksort($core->branches);
			$unknownbranches = 0;
			foreach ($core->branches as $k=>$branch)
				$branches[$k] = 'UNKNOWN'.$unknownbranches++;
		}
		if (!isset($addresses[$core->initialoffset]['name']) && (isset($this->main->opts['name'])))
			$addresses[$core->initialoffset]['name'] = $this->main->opts['name'];
		if (!isset($addresses[$core->initialoffset]['description']) && (isset($this->main->opts['desc'])))
			$addresses[$core->initialoffset]['description'] = $this->main->opts['desc'];
		$addresses[$core->initialoffset]['type'] = 'assembly';
		$addresses[$core->initialoffset]['size'] = $core->currentoffset-$core->initialoffset;
		foreach ($core->getMisc() as $k=>$val)
			$addresses[$core->initialoffset][$k] = $val;
		if ($branches != null)
			$addresses[$core->initialoffset]['labels'] = $branches;
		ksort($addresses);
		$output = preg_replace_callback('/^(\d+):/m', 'hexafixer', yaml_emit($gameorig,YAML_UTF8_ENCODING).yaml_emit($addresses,YAML_UTF8_ENCODING));
		file_put_contents(sprintf('games/%1$s/%1$s.yml',$this->main->gameid), $output);
	}
	public static function shouldhandle() {
		global $offset, $addresses;
		if (!isset($addresses[$offset]['type']) || ($addresses[$offset]['type'] !== 'data'))
			return true;
		return false;
	}
	public function getTemplate() {
		return core::template;
	}
}
?>