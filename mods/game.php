<?php
require 'src/platformFactory.php';
require 'src/addressFactory.php';
class game extends coremod {
	const magic = '';
	public function toBinaryData($argv, $data) {
	
	}
	public function getData($argv, $query = '') {
		global $settings;
		//Determine which game to work with
		$gameid = $settings['gameid'];
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%1$s/%1$s.yml', $argv[0])))
			$gameid = $argv[0];
			
		debugmessage("Loading cached data", 'info');
		//Load game data. from cache if possible
		AddressFactory::loadGame($gameid);
		$game = AddressFactory::getGameMetadata();
		$game['id'] = $gameid;
		$platform = platformFactory::getPlatform($game['Platform']);
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
		$this->metadata['submods'] = array();
		for ($dir = opendir('./mods/game/'); $file = readdir($dir); ) {
			if (substr($file, -4) == ".php") {
				require_once './mods/game/' . $file;
				$modClass = substr($file,0, -4);
				$magic = $modClass::getMagicValue();
				if ($magic !== null) {
					$magicvalues[] = $magic;
					foreach ($modClass::getMenuEntries($platform) as $url=>$entrylabel)
						$this->metadata['submods'][$url] = $entrylabel;
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
		$cpu = cpuFactory::getCPU($game['Platform']);
		$source = $platform;
		$cpu->setPlatform($source);
		debugmessage("Determining location", 'info');
		$magic = false;
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], $magicvalues)) {
				$offset = $argv[1];
				$magic = true;
			} else {
				$offset = addressFactory::getAddressFromName($argv[1]);
				if (is_numeric('0x'.$argv[1]) && $platform->isInRange(hexdec($argv[1])))
					$offset = hexdec($argv[1]);
				debugvar($offset, 'Location');
			}
		}
		if ($offset == -1)
			$offset = $cpu->getDefault();
		//What are we doing?
		$addressEntry = array();
		if (!$magic) {
			$addressEntry = addressFactory::getAddressEntryFromOffset($offset);
			if ($addressEntry == null)
				$addressEntry = array();
		}
		if (in_array($offset, $magicvalues, true)) {
			$modname = $offset;
			if (isset($argv[2]))
				$offset = $argv[2];
		} else
			if (isset($addressEntry['Type']))
				switch($addressEntry['Type']) {
					case 'data': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'int': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'array': $modname = isset($addressEntry['Item Type']) ? 'table' : 'hex'; break;
					case 'struct': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'script': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'palette': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'color': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'unknown': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'pointer': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'bytearray': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'bitfield': $modname = isset($addressEntry['Entries']) ? 'table' : 'hex'; break;
					case 'empty': $modname = 'hex'; break;
					default: $modname = 'asm'; break;
				}
			else
				$modname = 'asm';
		if (isset($omodname))
			$modname = $omodname;
		$module = new $modname();
		$platform->init();
		if (is_int($offset) && ($offset > 0)) {
			$source->seekTo($offset);
			if (isset($addressEntry['Filters']))
				foreach ($addressEntry['Filters'] as $filter) {
					require_once 'filters/'.$filter.'.php';
					$source = new $filter($source);
				}
		}
		$module->setDataSource($source);
		$module->setAddress($addressEntry);
		$module->setMetadata($this->metadata);
		$module->setGameData($game);
		$module->init($offset);
		$this->metadata['addrformat'] = $cpu->addressformat();
		
		if (isset($addressEntry['Name']) && ($addressEntry['Name'] != ""))
			$this->metadata['offsetname'] = $addressEntry['Name'];
		else
			$this->metadata['offsetname'] = sprintf($this->metadata['addrformat'], $source->currentOffset());
		$output = $module->execute($offset);
		$tmpdesc = $module->getDescription();
		
		if ($tmpdesc != '')
			$this->metadata['description'] = $tmpdesc;
		else if (isset($addressEntry['Description']))
			$this->metadata['description'] = $addressEntry['Description'];
		else if (isset($addressEntry['Name']))
			$this->metadata['description'] = $addressEntry['Name'];
		else
			$this->metadata['description'] = sprintf($this->metadata['addrformat'], $source->currentOffset());
		if (isset($addressEntry['Notes']))
			$this->metadata['notes'] = $addressEntry['Notes'];
		$this->metadata['template'] = $module->getTemplate();
		$nextoffset = $source->currentOffset();
		if (isset($addressEntry['Size']))
			$nextoffset = $offset + $addressEntry['Size'];
		if (!$magic) {
			$nextEntry = addressFactory::getAddressEntryFromOffset($nextoffset);
			if ($nextEntry === null)
				$nextEntry = array();
		}
		if (isset($nextEntry['Name']) && ($nextEntry['Name'] != ""))
			$this->metadata['nextoffset'] = $nextEntry['Name'];
		else
			$this->metadata['nextoffset'] = sprintf($this->metadata['addrformat'], $nextoffset);
		//$this->metadata = array_merge($this->metadata, $module->getMetadata());
		return $output;
	}
}
?>
