<?php
require 'platforms/platformFactory.php';
class game {
	const magic = '';
	
	function __construct() {
		global $settings, $display, $argv, $metadata;
		//Determine which game to work with
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%1$s/%1$s.yml', $argv[0])))
			$gameid = $argv[0];
		else
			$gameid = $settings['gameid'];
			
		debugmessage("Loading cached data", 'info');
		//Load game data. from cache if possible
		list($game, $addresses) = $this->loadYAML($gameid);
		
		$platform = platformFactory::getPlatform($game['platform']);
		$rom = new rawData(null);
		$rom->open($settings['rompath'].'/'.$gameid.'.'.$platform::extension);
		$platform->setDataSource($rom, 'rom');
		
		$magicvalues = array();
		$metadata['title'] = gametitle($game);
		$metadata['coremod'] = $gameid;
		
		debugmessage("Loading Modules", 'info');
		//Load Modules
		for ($dir = opendir('./mods/game/'); $file = readdir($dir); ) {
			if (substr($file, -4) == ".php") {
				require_once './mods/game/' . $file;
				$modClass = substr($file,0, -4);
				if (defined("$modClass::magic")) {
					$magicvalues[] = $modClass::magic;
					if (defined("$modClass::title"))
						$metadata['submods'][$modClass::magic] = $modClass::title;
				} else
					$othermods[] = $modClass;
			}
		}
		asort($metadata['submods']);
		debugmessage("Determining location", 'info');
		//Where are we?
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], $magicvalues))
				$offset = $argv[1];
			else {
				$tval = $argv[1];
				if (is_numeric('0x'.$argv[1]) && $platform->isInRange(hexdec($argv[1])))
					$offset = $tval = hexdec($argv[1]);
				if (isset($addresses[$tval]['offset']))
					$offset = $addresses[$tval]['offset'];
				debugvar($offset, 'Location');
			}
		}
		$metadata['offsetname'] = decimal_to_function($offset);
		debugvar(sprintf('%f seconds', microtime(true) - $GLOBALS['time_start']), 'Pre-module time');
		//What are we doing?
		if (in_array($offset, $magicvalues, true))
			$modname = $offset;
		else
			foreach ($othermods as $mod)
				if ($mod::shouldhandle($offset))
					$modname = $mod;
		$module = new $modname();
		$metadata['description'] = $module->description();
		$module->setPlatform($platform);
		$module->setAddresses($addresses);
		$output = $module->execute($offset);
		$display->mode = $modname;
		if (method_exists($module, 'getTemplate'))
			$display->mode = $module->getTemplate();
			
		//Display stuff
		$display->displaydata += $metadata;
		switch($GLOBALS['format']) {
		case 'yml':
			//if ($this->opts['dump']) {
				header('Content-Type: text/plain; charset=UTF-8');
			//} else {
			//	header('Content-Type: text/yaml; charset=UTF-8');
			//}
			if ($output !== null)
				foreach ($output as $yamldoc)
					echo yaml_emit($yamldoc, YAML_UTF8_ENCODING, YAML_ANY_BREAK);
			break;
		case 'json':
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
			break;
		default:
			$display->display($output); break;
		}
		debugvar(sprintf('%f MB', memory_get_usage()/1024/1024), 'Total Memory usage');
		debugvar(sprintf('%f seconds', microtime(true) - $GLOBALS['time_start']), 'Total Execution time');
	}
	public function loadYAML($id) {
		if ($GLOBALS['settings']['cache']) {
			if (isset($this->cache[sprintf('MPASM.ymlmodified.%s', $id)]) && ($this->cache[sprintf('MPASM.ymlmodified.%s', $id)] === filemtime(sprintf('games/%1$s/%1$s.yml', $id)))) {
				$this->debugmessage(sprintf("Game data (%s) loaded from cache", $id), 'info');
				list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)];
			} else { //Load game data & platform class from yml
				list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)] = yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1);
				$this->cache[sprintf('MPASM.ymlmodified.%s', $id)] = filemtime(sprintf('games/%1$s/%1$s.yml', $id));
			}
			return array($game,$addresses);
		} else 
			return yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1);
	}
}
?>