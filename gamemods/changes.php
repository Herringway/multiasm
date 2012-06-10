<?php
class changes extends gamemod {
	const magic = 'changes';
	const title = 'Changelog';
	public function execute() {
		global $gameid, $metadata;
		if (file_exists('games/' . $gameid . '/.git')) {
			exec('git --git-dir ./games/'.$gameid.'/.git log', $data);
			foreach ($data as $line) {
				$tmp = explode(' ', $line);
				if ($tmp[0] == 'commit') {
					if (isset($buff)) {
						$output[] = $buff;
						unset($buff);
					}
					$buff['version'] = $tmp[1];
					$metadata['menuitems'][$tmp[1]] = substr($tmp[1], 0, 10);
				} else if ($tmp[0] == 'Author:') {
					$buff['author'] = implode(' ', array_slice($tmp, 1));
				} else if ($tmp[0] == 'Date:') {
					$buff['date'] = trim(implode(' ', array_slice($tmp, 1)));
				} else if (trim($line) != '')
					$buff['description'][] = trim($line);
			}
		} else
			$output = array('No changelog found');
		return $output;
	}
}
?>