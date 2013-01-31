<?php
class changes extends gamemod {
	public static function getMagicValue() { return 'changes'; }
	public static function getMenuEntries($s) { return array('changes' => 'Changelog'); }
	public function getDescription() { return 'Changelog'; }
	public function getTemplate() { return 'changes'; }
	public function execute() {
		if (file_exists('games/' . $this->game['id'] . '/.git')) {
			exec('git --git-dir ./games/'.$this->game['id'].'/.git log', $data);
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
		return $output;
	}
}
?>