<?php
class core_changes {
	const magic = 'changes';
	function __construct() {
		global $display, $metadata, $game;
		$display->mode = 'changes';
		if (file_exists('.git')) {
			exec('git log', $data);
			foreach ($data as $line) {
				$tmp = explode(' ', $line);
				if ($tmp[0] == 'commit') {
					if (isset($buff)) {
						$metadata['menuitems'][$tmp[1]] = substr($buff['version'], 0, 10).' ('.$buff['author'].')';
						$output[] = $buff;
						unset($buff);
					}
					$buff['version'] = $tmp[1];
				} else if ($tmp[0] == 'Author:') {
					$authorstring = implode(' ', array_slice($tmp, 1));
					$authorsplit = explode('<', $authorstring);
					$buff['author'] = trim($authorsplit[0]);
					$buff['authoremail'] = substr($authorsplit[1],0,-1);
				} else if ($tmp[0] == 'Date:') {
					$buff['date'] = trim(implode(' ', array_slice($tmp, 1)));
				} else if (trim($line) != '')
					$buff['description'][] = trim($line);
			}
		} else
			$output = array('No changelog found');
		$metadata['title'] = 'MPASM';
		$metadata['description'] = trim(`cat .git/HEAD |awk -F'/' '{print $3}'`);
		$metadata['routinename'] = 'Changelog';
		$metadata['offsetname'] = '';
		$metadata['coremod'] = 'changes';
		$display->displaydata += $metadata;
		$display->display($output);
	}
}
?>