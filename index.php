<?php
require_once 'libs/commonfunctions.php';
require_once 'libs/rom.php';
require_once 'libs/cache.php';
require_once 'libs/settings.php';


ob_start();
class Main {
	public static $instance;
	public $settings;
	public $platform;
	public $core;
	public $game;
	public $gameid;
	public $rom;
	public $addresses;
	public $offset;
	public $offsetname;
	public $opts;
	public $nextoffset;
	public $dataname;
	public $comments;
	public $realdesc = '';
	public $godpowers = false;
	public $menuitems = array();
	public $gamelist = array();
	public $format;
	
	private function __construct() { }
	
	public function execute() {
		$time_start = microtime(true);
		$this->settings = new settings('settings.yml');
		
		if (PHP_SAPI === 'cli')
			require_once 'cli.php';
		else
			require_once 'web.php';
		
		$this->cache = new cache();
		
		$display = new display();
		$argv = $display->getArgv();
		$this->format = $display->getFormat();
		
		//Some debug output
		$this->debugvar($_SERVER, 'Server');
		$this->debugvar($argv, 'args');
		
		//Options!
		$this->opts = $display->getOpts($argv);
		$this->debugvar($this->opts, 'options');
		$this->godpowers = $display->canWrite();
		if ($this->settings['gamemenu'])
			for ($dir = opendir('./games/'); $file = readdir($dir); ) {
				if (($file[0] != '.') && is_dir('./games/'.$file)) {
					$game = yaml_parse_file('./games/'.$file.'/'.$file.'.yml', 0);
					$this->gamelist[$file] = $game['title'];
				}
			}
		//Determine which game to work with
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%1$s/%1$s.yml', $argv[0])))
			$this->gameid = $argv[0];
		else
			$this->gameid = $this->settings['gameid'];
			
		$this->debugmessage("Loading cached data");
		//Load game data. from cache if possible
		list($this->game, $this->addresses) = $this->loadYAML($this->gameid);
		
		require_once sprintf('platforms/%s.php', $this->game['platform']);
		if (!file_exists($this->settings['rompath'].'/'.$this->gameid.'.'.platform::extension))
			die ('Could not locate source data!');
		$this->game['size'] = filesize($this->settings['rompath'].'/'.$this->gameid.'.'.platform::extension);
		
		rom::get($this->settings['rompath'].'/'.$this->gameid.'.'.platform::extension);
		//$this->platform = platform::get();
		
		$this->debugmessage("Loading CPU Core");
		
		//Load CPU Class
		
		$cpu = $this->game['processor'];
		if (isset($known_addresses[$this->offset]['cpu'])) 
			$cpu = $this->addresses[$this->offset]['cpu']; //Override if game data sez so
		require_once sprintf('cpus/%s.php', $cpu);
		
		$magicvalues = array();
		
		$this->debugmessage("Loading Modules");
		//Load Modules
		for ($dir = opendir('./mods/'); $file = readdir($dir); ) {
			if (substr($file, -4) == ".php") {
				require_once './mods/' . $file;
				$modClass = substr($file,0, -4);
				if (defined("$modClass::magic"))
					$magicvalues[] = $modClass::magic;
				else
					$othermods[] = $modClass;
			}
		}
		
		$this->debugmessage("Determining location");
		//Where are we?
		$this->offset = core::get()->getDefault();
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], $magicvalues))
				$this->offset = $argv[1];
			else {
				if (isset($this->addresses) && !is_numeric('0x'.$argv[1])) {
					foreach ($this->addresses as $k => $addr)
						if (isset($addr['name']) && ($addr['name'] == $argv[1])) {
							$this->offset = $k;
							if (isset($addr['description']))
								$this->realdesc = $addr['description'];
							break;
						}
				} else {
					$this->offset = hexdec($argv[1]);
				}
			}
		}
		$this->debugvar($this->offset, 'Location');
		
		$this->debugvar(sprintf('%f seconds', microtime(true) - $time_start), 'Pre-module time');
		//What are we doing?
		if (in_array($this->offset, $magicvalues, true))
			$modname = $this->offset;
		else
			foreach ($othermods as $mod)
				if ($mod::shouldhandle())
					$modname = $mod;
		$module = new $modname();
		$output = $module->execute();
		$display->mode = $modname;
		if (method_exists($module, 'getTemplate'))
			$display->mode = $module->getTemplate();
			
		//Display stuff
		switch($this->format) {
		case 'yml':
			if ($this->opts['dump']) {
				header('Content-Type: text/plain; charset=UTF-8');
			} else {
				header('Content-Type: text/yaml; charset=UTF-8');
			}
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
		$this->debugvar(sprintf('%f MB', memory_get_usage()/1024/1024), 'Total Memory usage');
		$this->debugvar(sprintf('%f seconds', microtime(true) - $time_start), 'Total Execution time');
	}
	public function getOffsetName($offset, $onlyifexists = false) {
		if ($onlyifexists)
			return isset($this->addresses[$offset]['name']) ? $this->addresses[$offset]['name'] : '';
		return isset($this->addresses[$offset]['name']) ? $this->addresses[$offset]['name'] : sprintf(core::addressformat, $offset);
	}
	public function getDataBlock($ioffset) {
		$offset = $ioffset;
		for (;!isset($this->addresses[$offset]) && ($offset > 0); $offset--);
		if (!isset($this->addresses[$offset]) || ($ioffset - $offset > $this->addresses[$offset]['size']))
			return -1;
		return $offset;
	}
	public function loadYAML($id) {
		if ($this->settings['cache']) {
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
	public function decimal_to_function($input) {
		return (isset($this->addresses[$input]['name']) && ($this->addresses[$input]['name'] != "")) ? $this->addresses[$input]['name'] : sprintf(core::addressformat, $input);
	}
	function debugvar($var, $label) {
		if ($this->settings['debug'])
			display::debugvar($var, $label);
	}
	function debugmessage($msg, $level = 'error') {
		if ($this->settings['debug'])
			display::debugmessage($msg,$level);
	}
	
	public static function get() {
		if (!isset(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}
}
Main::get()->execute();
?>
