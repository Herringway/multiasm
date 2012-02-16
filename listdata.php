<?php
function listdata() {
	global $game, $known_addresses_p, $handle, $gameid;
	$platform = new platform($handle);
	$output = array();
	foreach ($known_addresses_p as $addr=>$data) {
		try {
			$realaddr = $platform->map_rom($addr);
			if ($realaddr !== null)
				$output[] = array('address' => isset($_GET['real_address']) ? $realaddr : $addr, 'type' => $data['type'], 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => $data['size']);
		} catch (Exception $e) { }
	}
	$dwoo = new Dwoo();
	$dwoo->output('templates/listdata.tpl', array('game' => $gameid, 'title' => $game['title'], 'data' => $output));
}
?>