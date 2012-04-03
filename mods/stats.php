<?php
class stats {
	private $main;
	
	const magic = 'stats';
	
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$stats = array();
		$counteddata = 0;
		$biggest = array('size' => 0, 'offset' => 0);
		$biggestroutine = array('size' => 0, 'offset' => 0);
		$divisions = array();
		$routines = array();
		$biggest = $biggestroutine = array('size' => 0, 'name' => 'undefined');
		foreach ($this->main->addresses as $k => $entry) {
			if (!platform::get()->isRom($k))
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
		if ($counteddata < $this->main->game['size'])
			$divisions['Unknown'] = $this->main->game['size'] - $counteddata;
		$stats['Size'] = $divisions;
		//$this->main->yamldata[] = $stats;
		$this->main->dataname = 'ROM Stats';
		return array($stats);
	}
}
?>