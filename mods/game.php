<?php
require 'platforms/platformFactory.php';
require 'libs/addressFactory.php';
class game {
	const magic = '';
	private $metadata;
	public function getMetadata() {
		return $this->metadata;
	}
	public function execute($argv) {
		global $settings;
		//Determine which game to work with
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%1$s/%1$s.yml', $argv[0])))
			$gameid = $argv[0];
		else
			$gameid = $settings['gameid'];
			
		debugmessage("Loading cached data", 'info');
		//Load game data. from cache if possible
		list($game, $addresses) = AddressFactory::getAddresses($gameid);
		
		$platform = platformFactory::getPlatform($game['platform']);
		$rom = new rawData(null);
		$rom->open($settings['rompath'].'/'.$gameid.'.'.$platform::extension);
		$platform->setDataSource($rom, 'rom');
		
		$magicvalues = array();
		$this->metadata['hideme'] = true;
		$this->metadata['title'] = gametitle($game);
		$this->metadata['coremod'] = $gameid;
		
		debugmessage("Loading Modules", 'info');
		//Load Modules
		for ($dir = opendir('./mods/game/'); $file = readdir($dir); ) {
			if (substr($file, -4) == ".php") {
				require_once './mods/game/' . $file;
				$modClass = substr($file,0, -4);
				if (defined("$modClass::magic")) {
					$magicvalues[] = $modClass::magic;
					if (defined("$modClass::title"))
						$this->metadata['submods'][$modClass::magic] = $modClass::title;
				} else
					$othermods[] = $modClass;
			}
		}
		asort($this->metadata['submods']);
		debugmessage("Determining location", 'info');
		//Where are we?
		$offset = -1;
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], $magicvalues))
				$offset = $argv[1];
			else {
				if (isset($addresses[$argv[1]]['offset']))
					$offset = $addresses[$argv[1]]['offset'];
				if (is_numeric('0x'.$argv[1]) && $platform->isInRange(hexdec($argv[1])))
					$offset = hexdec($argv[1]);
				debugvar($offset, 'Location');
			}
		}
		$this->metadata['offsetname'] = decimal_to_function($offset);
		debugvar(sprintf('%f seconds', microtime(true) - $GLOBALS['time_start']), 'Pre-module time');
		//What are we doing?
		if (in_array($offset, $magicvalues, true))
			$modname = $offset;
		else
			if (isset($addresses[$offset]['type']))
				switch($addresses[$offset]['type']) {
					case 'data': $modname = 'table'; break;
					default: $modname = 'asm'; break;
				}
			else
				$modname = 'asm';
			//foreach ($othermods as $mod)
			//	if ($mod::shouldhandle($offset))
			//		$modname = $mod;
		$module = new $modname();
		$this->metadata['description'] = $module->getDescription();
		$module->setDataSource($platform);
		$module->setAddresses($addresses);
		$module->setGameData($game);
		$this->metadata['template'] = $module->getTemplate();
		$output = $module->execute($offset);
		$this->metadata = array_merge($this->metadata, $module->getMetadata());
		return $output;
	}
}
?>