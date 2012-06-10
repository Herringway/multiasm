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
						$output[] = $buff;
						unset($buff);
					}
					$buff['version'] = $tmp[1];
					$display->displaydata['menuitems'][$tmp[1]] = substr($tmp[1], 0, 10);
				} else if ($tmp[0] == 'Author:') {
					$buff['author'] = implode(' ', array_slice($tmp, 1));
				} else if ($tmp[0] == 'Date:') {
					$buff['date'] = trim(implode(' ', array_slice($tmp, 1)));
				} else if (trim($line) != '')
					$buff['description'][] = trim($line);
			}
		} else
			$output = array('No changelog found');
		$display->displaydata['title'] = trim(`cat .git/HEAD |awk -F'/' '{print $3}'`);
		$display->displaydata['routinename'] = 'Changelog';
		$display->displaydata['offsetname'] = '';
		$display->displaydata['coremod'] = 'changes';
		$display->display($output);
	}
}
?>