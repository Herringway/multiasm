<?php
class issues extends gamemod {
	const magic = 'issues';
	const title = 'Issues';
	public function execute() {
		$allproblems = array();
		$prev = 0;
		foreach ($this->addresses as $offset => $entry) {
			if (!is_numeric($offset))
				continue;
			if ($this->source->identifyArea($offset) != 'rom')
				continue;
			if (isset($entry['Ignore']) && ($entry['Ignore'] == true))
				continue;
			$problems = array();
			if ($prev > $offset)
				$problems[] = 'Overlap detected';
			if (!isset($entry['size']))
				$problems[] = 'No size defined';
			if (!isset($entry['Type']))
				$problems[] = 'No type defined';
			if (!isset($entry['name']) && (!isset($entry['Type']) || ($entry['Type'] != 'empty')))
				$problems[] = 'No name defined';
			if (!isset($entry['description']) && (!isset($entry['Type']) || ($entry['Type'] != 'empty')))
				$problems[] = 'No description defined';
			if ($problems != array())
				$allproblems[$offset] = $problems;
			//if (isset($entry['size']) && !isset($this->addresses[$offset+$entry['size']]) && ($this->platform->map_rom($offset+$entry['size']) < $this->game['size']))
			//	$allproblems[decimal_to_function($offset+$entry['size'])] = array('Undefined area!');
			$prev = $offset+(isset($entry['size']) ? $entry['size'] : 0);
		}
		return array($allproblems);
	}
	public function getTemplate() {
		return 'issues';
	}
}
?>