<?php
class allasm {
	
	const magic = 'allasm';
	
	public function execute() {
		global $settings, $addresses, $core, $menuitems, $dataname;
		if (!$settings['debug'])
			return null;
		$limit = 900;
		//ini_set('memory_limit', '1024M');
		$core->dump = true;
		foreach ($addresses as $k => $entry) {
			if (isset($entry['type']) && ($entry['type'] === 'assembly') && (!isset($entry['ignore'])))
				if ($limit-- > 0)
					$output[(isset($addresses[$k]['name']) ? $addresses[$k]['name'] : sprintf(core::addressformat, $k))] = $core->execute($k);
		}
		$dataname = 'All ASM';
			
		foreach ($output as $branch => $stuff)
			$menuitems[$branch] = $branch;
		return $output;
	}
}
?>