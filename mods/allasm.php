<?php
class allasm {
	private $main;
	
	const magic = 'allasm';
	
	public function execute() {
		$limit = 600;
		//ini_set('memory_limit', '1024M');
		core::get()->dump = true;
		foreach (Main::get()->addresses as $k => $entry) {
			if (isset($entry['type']) && ($entry['type'] === 'assembly') && (!isset($entry['ignore'])))
				if ($limit-- > 0)
					$output[(isset(Main::get()->addresses[$k]['name']) ? Main::get()->addresses[$k]['name'] : sprintf(core::addressformat, $k))] = core::get()->execute($k);
		}
		Main::get()->dataname = 'All ASM';
			
		foreach ($output as $branch => $stuff)
			Main::get()->menuitems[$branch] = $branch;
		return $output;
	}
}
?>