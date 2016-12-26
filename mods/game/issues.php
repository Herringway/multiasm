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
			$problems = array();
			if ($prev > $entry['Offset'])
				$problems[] = 'Overlap detected ('.dechex($prev).')';
			if (!isset($entry['Size']))
				$problems[] = 'No size defined';
			if (!isset($entry['Type']))
				$problems[] = 'No type defined';
			if (!isset($entry['Offset']))
				$problems[] = 'No Offset defined';
			if (!isset($entry['Description']) && (!isset($entry['Type']) || ($entry['Type'] != 'empty')) && (substr($name,0,7) != 'UNKNOWN'))
				$problems[] = 'No description defined';
			if (isset($entry['Description']) && (strlen($entry['Description']) > 70))
				$problems[] = 'Consider shortening the description';
			if (isset($entry['labels']))
				$problems[] = 'Using old label format';
			if (isset($entry['Labels']))
				foreach ($entry['Labels'] as $k=>$v) {
					if ($k < 0)
						$problems[] = 'Negative label offset';
					if ($k > $entry['Size'])
						$problems[] = 'Label exceeds size of data';
					if ($k == 0)
						$problems[] = 'Label == base address';
				}
			if (isset($entry['accumSize']))
				$problems[] = 'Using old accum format';
			if (isset($entry['indexSize']))
				$problems[] = 'Using old index format';
			if (isset($entry['localvars']))
				$problems[] = 'Using old localvar format';
			if (isset($entry['Name']))
				$problems[] = 'has a name entry';
			if (isset($entry['name']))
				$problems[] = 'Using old pre-capitalized name';
			if (isset($entry['description']))
				$problems[] = 'Using old pre-capitalized description';
			if (isset($entry['Entries'])) {
				foreach ($entry['Entries'] as $k=>$v) {
					if (isset($v['Type']) && (($v['Type'] == 'binint') || ($v['Type'] == 'hexint')))
						$problems[] = 'Using hexint/binint instead of int with base parameter';
				}
			}
			//if (isset($entry['Size']) && !isset($this->addresses[$entry['Offset']+$entry['Size']]) && (addressFactory::getAddressFromOffset($entry['Offset']+$entry['Size']) == -1))
			//	$allproblems[sprintf("%X", $entry['Offset']+$entry['Size'])] = array('Undefined area!');
			$this->countProblems += count($problems);
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
