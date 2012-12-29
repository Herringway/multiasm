<?php
class core_changes {
	private $metadata;
	const magic = 'changes';
	public function getMetadata() {
		return $this->metadata;
	}
	function execute($arg) {
		if (file_exists('.git')) {
			exec('git log', $data);
			foreach ($data as $line) {
				$tmp = explode(' ', $line);
				if ($tmp[0] == 'commit') {
					if (isset($buff)) {
						$this->metadata['menuitems'][$tmp[1]] = substr($buff['version'], 0, 10).' ('.$buff['author'].')';
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
		$this->metadata['title'] = 'MPASM';
		$this->metadata['description'] = trim(`cat .git/HEAD |awk -F'/' '{print $3}'`);
		$this->metadata['routinename'] = 'Changelog';
		$this->metadata['offsetname'] = '';
		$this->metadata['coremod'] = 'changes';
		$this->metadata['template'] = 'changes';
		return $output;
	}
}
?>