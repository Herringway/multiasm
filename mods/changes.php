<?php
class changes {
	const magic = 'changes';
	public function execute() {
		if (file_exists('games/' . Main::get()->gameid . '/.git'))
			exec('git --git-dir ./games/'.Main::get()->gameid.'/.git log', $output);
		else
			$output = array('No changelog found');
		return $output;
	}
}
?>