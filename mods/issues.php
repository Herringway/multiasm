<?php
class issues {
	private $main;
	
	const magic = 'issues';
	
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$allproblems = array();
		$prev = 0;
		foreach ($this->main->addresses as $offset => $entry) {
			if (!$this->main->platform->isRom($offset))
				continue;
			if (isset($entry['ignore']) && ($entry['ignore'] == true))
				continue;
			$problems = array();
			if ($prev > $offset)
				$problems[] = 'Overlap detected';
			if (!isset($entry['size']))
				$problems[] = 'No size defined';
			if (($offset >= $this->main->platform->base()) && !isset($entry['type']))
				$problems[] = 'No type defined';
			if (!isset($entry['name']) && (!isset($entry['type']) || ($entry['type'] != 'empty')))
				$problems[] = 'No name defined';
			if (!isset($entry['description']) && (!isset($entry['type']) || ($entry['type'] != 'empty')))
				$problems[] = 'No description defined';
			if (isset($entry['size']) && !isset($this->main->addresses[$offset+$entry['size']]) && ($this->main->platform->map_rom($offset+$entry['size']) < $this->main->game['size']))
				$allproblems[$this->main->decimal_to_function($offset+$entry['size'])] = array('Undefined area!');
			if ($problems != array())
				$allproblems[$this->main->decimal_to_function($offset)] = $problems;
			$prev = $offset+(isset($entry['size']) ? $entry['size'] : 0);
		}
		
		$this->main->dataname = 'Issues';
		return $allproblems;
	}
}
?>