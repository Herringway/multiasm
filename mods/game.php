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
		$gameid = $settings['gameid'];
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%1$s/%1$s.yml', $argv[0])))
			$gameid = $argv[0];
			
		debugmessage("Loading cached data", 'info');
		//Load game data. from cache if possible
		list($game, $addresses) = AddressFactory::getAddresses($gameid);
		$game['id'] = $gameid;
		
		$platform = platformFactory::getPlatform($game['platform']);
		for ($dir = opendir($settings['rompath']); $file = readdir($dir);) {
			$d = explode('.', $file);
			if ($d[0] == $gameid) {
				dprintf('found %s for %s', $d[1], $gameid);
				$source = new rawData(null);
				$source->open($settings['rompath'].'/'.$file);
				$platform->setDataSource($source, $d[1]);
			}
		}
		
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
		//Where are we?
		$offset = -1;
		if (isset($argv[1])) {
			$colonsplit = explode(':', $argv[1]);
			if (count($colonsplit) > 1) {
				if (($colonsplit[0] == 'hex') || ($colonsplit[0] == 'asm')) {
					$argv[1] = $colonsplit[1];
					$omodname = $colonsplit[0];
				}
			}
		}
		$cpu = cpuFactory::getCPU($game['processor']);
		debugmessage("Determining location", 'info');
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
		//What are we doing?
		if (in_array($offset, $magicvalues, true))
			$modname = $offset;
		else
			if (isset($addresses[$offset]['type']))
				switch($addresses[$offset]['type']) {
					case 'data': $modname = isset($addresses[$offset]['entries']) ? 'table' : 'hex'; break;
					default: $modname = 'asm'; break;
				}
			else
				$modname = 'asm';
		if (isset($omodname))
			$modname = $omodname;
		$module = new $modname();
		$source = $platform;
		if (is_int($offset) && ($offset > 0)) {
			$source->seekTo($offset);
			if (isset($addresses[$offset]['filters']))
				foreach ($addresses[$offset]['filters'] as $filter) {
					require_once 'filters/'.$filter.'.php';
					$source = new $filter($source);
				}
		}
		$module->setDataSource($source);
		if (!isset($addresses[$offset]))
			$addresses[$offset] = array();
		//var_dump($addresses[$offset]);
		$module->setAddress($addresses[$offset]);
		$module->setAddresses($addresses);
		$module->setMetadata($this->metadata);
		$module->setGameData($game);
		$module->init($offset);
		if (isset($addresses[$offset]['name']) && ($addresses[$offset]['name'] != ""))
			$this->metadata['offsetname'] = $addresses[$offset]['name'];
		else
			$this->metadata['offsetname'] = sprintf($cpu->addressFormat(), $source->currentOffset());
		$tmpdesc = $module->getDescription();
		$this->metadata['addrformat'] = $cpu->addressformat();
		if ($tmpdesc != '')
			$this->metadata['description'] = $tmpdesc;
		else if (isset($addresses[$source->currentOffset()]['description']))
			$this->metadata['description'] = $addresses[$source->currentOffset()]['description'];
		else if (isset($addresses[$source->currentOffset()]['name']))
			$this->metadata['description'] = $addresses[$source->currentOffset()]['name'];
		else {
			$this->metadata['description'] = sprintf($cpu->addressformat(), $source->currentOffset());
		}
		$this->metadata['template'] = $module->getTemplate();
		$output = $module->execute($offset);
		$nextoffset = $source->currentOffset();
		if (isset($addresses[$offset]['size']))
			$nextoffset = $offset + $addresses[$offset]['size'];
		if (isset($addresses[$nextoffset]['name']) && ($addresses[$nextoffset]['name'] != ""))
			$this->metadata['nextoffset'] = $addresses[$nextoffset]['name'];
		else {
			$cpu = cpuFactory::getCPU($game['processor']);
			$this->metadata['nextoffset'] = sprintf($cpu->addressFormat(), $nextoffset);
		}
		//$this->metadata = array_merge($this->metadata, $module->getMetadata());
		return $output;
	}
	private function decimal_to_function($input) {
		return (isset($addresses[$input]['name']) && ($addresses[$input]['name'] != "")) ? $addresses[$input]['name'] : sprintf(core::addressformat, $input);
	}
}
?>