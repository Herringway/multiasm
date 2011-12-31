<?php
function showstats() {
	global $known_addresses, $gameid, $game;
	$stats = array();
	$counteddata = 0;
	$biggest = array('size' => 0, 'offset' => 0);
	$biggestroutine = array('size' => 0, 'offset' => 0);
	$divisions = array();
	$routines = array();
	foreach ($known_addresses as $k => $entry) {
		if ($k < 0xC00000)
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
		if (($k >= 0xC00000) && (isset($entry['size'])))
			$counteddata += $entry['size'];
	}
	$stats['Known_Data'] = $counteddata;
	$stats['Biggest'] = $biggest;
	$stats['Biggest_Routine'] = $biggestroutine;
	if ($counteddata < $game['size'])
		$divisions['Unknown'] = $game['size'] - $counteddata;
	$stats['Size'] = $divisions;
	$dwoo = new Dwoo();
	$dwoo->output('templates/gamestats.tpl', array('stats' => $stats, 'game' => $gameid, 'title' => $game['title'], 'routines' => (isset($_GET['routinelist']) ? $routines : null)));
}
if (array_search(__FILE__,get_included_files()) == 0) {
	require_once 'Dwoo/dwooAutoload.php';
	require_once 'commonfunctions.php';

	$gameid = 'eb';
	$rompath = '../rms/';

	$argc = (isset($_SERVER['PATH_INFO']) ? explode('/', $_SERVER['PATH_INFO']) : array('',''));
	if (isset($argc[1]) && ($argc[1] != null) && is_dir('./games_defines/'.$argc[1].'asm'))
		$gameid = $argc[1];

	$game = yaml_parse_file('games_defines/'.$gameid.'asm/game.yml');

	$known_addresses = yaml_parse_file('games_defines/'.$gameid.'asm/known_offsets.yml');
	showstats();
}
?>
