<?php
function diagnose() {
	global $game, $known_addresses,$gameshort;
	$allproblems = array();
	$prev = 0;
	foreach ($known_addresses as $offset => $entry) {
		if ($offset < $game['rombase'])
			continue;
		if (isset($entry['ignore']) && ($entry['ignore'] == true))
			continue;
		$problems = array();
		if ($prev > $offset)
			$problems[] = 'Overlap detected';
		if (!isset($entry['size']))
			$problems[] = 'No size defined';
		if (($offset >= $game['rombase']) && !isset($entry['type']))
			$problems[] = 'No type defined';
		if (!isset($entry['name']) && (!isset($entry['type']) || ($entry['type'] != 'nullspace')))
			$problems[] = 'No name defined';
		if (!isset($entry['description']) && (!isset($entry['type']) || ($entry['type'] != 'nullspace')))
			$problems[] = 'No description defined';
		if (isset($entry['size']) && !isset($known_addresses[$offset+$entry['size']]) && ($offset+$entry['size'] < $game['rombase']+$game['size']))
			$allproblems[$offset+$entry['size']] = array('Undefined area!');
		if ($problems != array())
			$allproblems[(isset($entry['name']) ? $entry['name'] : strtoupper(dechex($offset)))] = $problems;
		$prev = $offset+(isset($entry['size']) ? $entry['size'] : 0);
	}
	$dwoo = new Dwoo();
	$dwoo->output('templates/diagnostic.tpl', array('problems' => $allproblems, 'gameshort' => $gameshort, 'title' => $game['title']));
}
?>
