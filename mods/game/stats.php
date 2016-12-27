<?php
class stats extends gamemod {
	public static function getMagicValue() { return 'stats'; }
	public static function getMenuEntries($s) { return array('stats' => 'Stats'); }
	public function getTemplate() { return 'stats'; }
	public function getDescription() { return 'Game Stats'; }
	
	public function execute($arg) {
		$stats = array();
		$counteddata = 0;
		$biggest = array('size' => 0, 'offset' => 0);
		$biggestroutine = array('size' => 0, 'offset' => 0);
		$divisions = array();
		$routines = array();
		$biggest = $biggestroutine = array('size' => 0, 'name' => 'undefined');
		foreach (addressFactory::getAddresses() as $name=>$entry) {
			if ($this->source->identifyArea($entry['Offset']) != 'rom')
				continue;
			if (isset($entry['Size'])) {
				if (!isset($entry['Type']))
					$entry['Type'] = 'Unknown';
				if (!isset($divisions[$entry['Type']]))
					$divisions[$entry['Type']] = 0;
				$divisions[$entry['Type']] += $entry['Size'];
				if ($entry['Type'] == 'assembly')
					$routines[] = $name;
				if ($entry['Size'] > $biggest['size'])
					$biggest = array('size' => $entry['Size'], 'name' => $name);
				if (($entry['Size'] > $biggestroutine['size']) && isset($entry['Type']) && ($entry['Type'] == 'assembly'))
					$biggestroutine = array('size' => $entry['Size'], 'name' => $name);
			}
			if (isset($entry['Size']))
				$counteddata += $entry['Size'];
		}
		$stats['Known_Data'] = $counteddata;
		$stats['Biggest'] = $biggest;
		$stats['Biggest_Routine'] = $biggestroutine;
		$stats['miscdata'] = $this->source->getMiscInfo();
		$stats['Size'] = $divisions;
		return array($stats);
	}
}
?>