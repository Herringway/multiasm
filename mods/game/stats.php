<?php
class stats extends gamemod {
	const magic = 'stats';
	const title = 'Stats';
	
	public function execute($arg) {
		$stats = array();
		$counteddata = 0;
		$biggest = array('size' => 0, 'offset' => 0);
		$biggestroutine = array('size' => 0, 'offset' => 0);
		$divisions = array();
		$routines = array();
		$biggest = $biggestroutine = array('size' => 0, 'name' => 'undefined');
		foreach ($this->addresses as $k => $entry) {
			if (!is_numeric($k))
				continue;
			if ($this->platform->identifyArea($k) != 'rom')
				continue;
			if (isset($entry['ignore']) && ($entry['ignore']))
				continue;
			if (isset($entry['size'])) {
				if (!isset($entry['type']))
					$entry['type'] = 'Unknown';
				if (!isset($divisions[$entry['type']]))
					$divisions[$entry['type']] = 0;
				$divisions[$entry['type']] += $entry['size'];
				if (($entry['type'] == 'assembly') && isset($entry['name']))
					$routines[] = $entry['name'];
				if ($entry['size'] > $biggest['size'])
					$biggest = array('size' => $entry['size'], 'name' => !empty($entry['name']) ? $entry['name'] : sprintf('%06X', $k));
				if (($entry['size'] > $biggestroutine['size']) && isset($entry['type']) && ($entry['type'] == 'assembly'))
					$biggestroutine = array('size' => $entry['size'], 'name' => !empty($entry['name']) ? $entry['name'] : sprintf('%06X', $k));
			}
			if (isset($entry['size']))
				$counteddata += $entry['size'];
		}
		$stats['Known_Data'] = $counteddata;
		$stats['Biggest'] = $biggest;
		$stats['Biggest_Routine'] = $biggestroutine;
		$stats['miscdata'] = $this->platform->getMiscInfo();
		//if ($counteddata < $game['size'])
		//	$divisions['Unknown'] = $game['size'] - $counteddata;
		$stats['Size'] = $divisions;
		return array($stats);
	}
	public function getTemplate() {
		return 'stats';
	}
}
?>