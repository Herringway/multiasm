<?php
require_once '../hexview.php';
function process_entries($handle, $offset, $end, $entries) {
	global $game;
	$output = array();
	$offsets = array();
	while ($offset < $end) {
		$tmpoffset = $offset;
		foreach ($entries as $entry) {
			$bytesread = isset($entry['size']) ? $entry['size'] : 0;
			if (!isset($entry['type']) || ($entry['type'] == 'int')) {
				$num = read_int($handle, $entry['size']);
				if (isset($entry['values'][$num]))
					$tmparray[$entry['name']] = $entry['values'][$num];
				else if (isset($entry['bitvalues']))
					$tmparray[$entry['name']] = get_bit_flags2($num,$entry['bitvalues']);
				else
					$tmparray[$entry['name']] = $num;
			}
			else if ($entry['type'] == 'hexint')
				$tmparray[$entry['name']] = str_pad(strtoupper(dechex(read_int($handle, $entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
			else if ($entry['type'] == 'pointer')
				$tmparray[$entry['name']] = strtoupper(dechex(read_int($handle, $entry['size'])));
			else if ($entry['type'] == 'palette')
				$tmparray[$entry['name']] = read_palette($handle, $entry['size']);
			else if ($entry['type'] == 'binary')
				$tmparray[$entry['name']] = decbin(read_int($handle, $entry['size']));
			else if ($entry['type'] == 'boolean')
				$tmparray[$entry['name']] = read_int($handle, $entry['size']) ? true : false;
			else if ($entry['type'] == 'tile')
				$tmparray[$entry['name']] = read_tile($handle, $entry['bpp']);
			else if (isset($game['texttables'][$entry['type']]))
				$tmparray[$entry['name']] = read_string($handle, $bytesread, $game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null);
			else
				$tmparray[$entry['name']] = read_bytes($handle, $entry['size']);
			$offset += $bytesread;
		}
		$output[] = $tmparray;
		$offsets[] = $tmpoffset;
		$tmparray = array();
	}
	return array($output, $offsets, $offset);
}
function showtable($offset) {
	global $handle, $known_addresses, $game, $gameid;
	$platform = new platform($handle, array());
	$realoffset = $platform->map_rom($offset);
	fseek($handle, $realoffset);
	$table = $known_addresses[$offset];

	$initialoffset = $offset;

	$tmparray = array();
	$output = array();
	$i = 0;
	$tablename = null;
	if (isset($table['description']))
		$tablename = $table['description'];
	if (!isset($table['entries']) || isset($_GET['forcehexview'])) {
		if (!isset($table['size']))
			die('Table has no size defined!');
		fseek($handle, $realoffset);
		$data = fread($handle, $known_addresses[$offset]['size']);
		$nextoffset = $offset+$known_addresses[$offset]['size'];
		if (isset($known_addresses[$offset]['charset']))
			$charset = $game['texttables'][$known_addresses[$offset]['charset']]['replacements'];
		else if (isset($game['defaulttext']))
			$charset = $game['texttables'][$game['defaulttext']]['replacements'];
		else
			$charset = null;
		$hex = hexview($data, isset($known_addresses[$offset]['width']) ? $known_addresses[$offset]['width'] : 16, $offset, $charset);
		$dwoo = new Dwoo();
		$dwoo->output('templates/'.$game['platform'].'_hex.tpl', array('hex' => $hex, 'title' => $tablename, 'game' => $gameid, 'nextoffset' => isset($known_addresses[$nextoffset]['name']) ? $known_addresses[$nextoffset]['name'] : strtoupper(dechex($nextoffset))));
		return;
	}
	$header = array();
	$headerend = $offset;
	if (isset($table['header']))
		list($header, $headerend) = process_entries($handle, $offset, $initialoffset+1, $table['header']);
	list($entries,$offsets,$offset) = process_entries($handle, $headerend, $initialoffset+$table['size'], $table['entries']);
	if (isset($_GET['YAML'])) {
		header('Content-Type: text/plain; charset=UTF-8');
		echo yaml_emit($table['entries']);
		echo yaml_emit($entries);
	} else if (isset($_GET['YAML_DATA'])) {
			header('Content-Type: text/plain; charset=UTF-8');
		echo yaml_emit($known_addresses);
	} else {
		header('Content-Type: text/html; charset=UTF-8');
		$dwoo = new Dwoo();
		$dwoo->output('templates/'.$game['platform'].'_table.tpl', array('header' => $header, 'offsets' => $offsets, 'entries' => $entries, 'title' => $tablename, 'game' => $gameid, 'nextoffset' => isset($known_addresses[$offset]['name']) ? $known_addresses[$offset]['name'] : strtoupper(dechex($offset))));
	}
}
?>
