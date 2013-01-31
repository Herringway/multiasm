<?php
class issues extends gamemod {
	public static function getMagicValue() { return 'issues'; }
	public static function getMenuEntries($s) { return array('issues' => 'Issues'); }
	public function getDescription() { return 'Issues'; }
	public function getTemplate() { return 'issues'; }
	public function execute() {
		$allproblems = array();
		$prev = 0;
		foreach (addressFactory::getAddresses() as $name => $entry) {
			if (isset($entry['Ignore']) && ($entry['Ignore'] == true))
				continue;
			$problems = array();
			if ($prev > $entry['Offset'])
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
				$allproblems[$name] = $problems;
			//if (isset($entry['size']) && !isset($this->addresses[$offset+$entry['size']]) && ($this->platform->map_rom($offset+$entry['size']) < $this->game['size']))
			//	$allproblems[decimal_to_function($offset+$entry['size'])] = array('Undefined area!');
			$prev = $entry['Offset']+(isset($entry['Size']) ? $entry['Size'] : 0);
		}
		return array($allproblems);
	}
}
?>