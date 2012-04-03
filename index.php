<?php
require_once 'chromephp.php';
require_once 'commonfunctions.php';
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
	//public $yamldata;
	public $dataname;
	public $comments;
	public $realdesc = '';
	public $godpowers = false;
	public $menuitems = array();
	public $gamelist = array();
	
	private function __construct() { }
	
	public function execute() {
		$time_start = microtime(true);
		require_once 'rom.php';
		if (!file_exists('settings.yml'))
			file_put_contents('settings.yml', yaml_emit(array('gameid' => 'eb', 'rompath' => '.', 'debug' => false, 'password' => 'changeme')));
		$this->settings = yaml_parse_file('settings.yml');
		
		if (PHP_SAPI === 'cli')
			require_once 'cli.php';
		else
			require_once 'web.php';
		require_once 'cache.php';
		
		$this->cache = new cache();
		
		$display = new display();
		$argv = $display->getArgv();
		
		//Some debug output
		$this->debugvar($_SERVER, 'Server');
		$this->debugvar($argv, 'args');
		
		//Options!
		$this->opts = $display->getOpts($argv);
		$this->debugvar($this->opts, 'options');
		$this->godpowers = $display->canWrite();
		if (!isset($this->settings['gamemenu']) || ($this->settings['gamemenu']))
			for ($dir = opendir('./games/'); $file = readdir($dir); ) {
				if (substr($file, -4) == ".yml") {
					$game = yaml_parse_file('./games/'.$file, 0);
					$this->gamelist[substr($file, 0, -4)] = $game['title'];
				}
			}
		//Determine which game to work with
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%s.yml', $argv[0])))
			$this->gameid = $argv[0];
		else
			$this->gameid = $this->settings['gameid'];
			
		//Load game data. from cache if possible
		list($this->game, $this->addresses) = $this->loadYAML($this->gameid);
		
		require_once sprintf('platforms/%s.php', $this->game['platform']);
		if (!file_exists($this->settings['rompath'].$this->gameid.'.'.platform::extension))
			die ('Could not locate source data!');
		$this->game['size'] = filesize($this->settings['rompath'].$this->gameid.'.'.platform::extension);
		
		rom::get($this->settings['rompath'].$this->gameid.'.'.platform::extension);
		//$this->platform = platform::get();
		
		
		
		//Load CPU Class
		
		$cpu = $this->game['processor'];
		if (isset($known_addresses[$this->offset]['cpu'])) 
			$cpu = $this->addresses[$this->offset]['cpu']; //Override if game data sez so
		require_once sprintf('cpus/%s.php', $cpu);
		$this->core = new core();
		
		$magicvalues = array();
		
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
		
		//Where are we?
		$this->offset = $this->core->getDefault();
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
		if (isset($this->opts['yaml'])) {
			header('Content-Type: text/plain; charset=UTF-8');
			if ($output !== null)
				foreach ($output as $yamldoc)
					echo yaml_emit($yamldoc);
		} else
			$display->display($output);
			
		$this->debugvar(sprintf('%f MB', memory_get_usage()/1024/1024), 'Total Memory usage');
		$this->debugvar(sprintf('%f seconds', microtime(true) - $time_start), 'Total Execution time');
	}
	public function getOffsetName($offset) {
		return isset($this->addresses[$offset]['name']) ? $this->addresses[$offset]['name'] : '';
	}
	public function getDataBlock($ioffset) {
		$offset = $ioffset;
		for (;!isset($this->addresses[$offset]) && ($offset > 0); $offset--);
		if (!isset($this->addresses[$offset]) || ($ioffset - $offset > $this->addresses[$offset]['size']))
			return -1;
		return $offset;
	}
	public function loadYAML($id) {
		if (isset($this->cache[sprintf('MPASM.ymlmodified.%s', $id)]) && ($this->cache[sprintf('MPASM.ymlmodified.%s', $id)] === filemtime(sprintf('games/%s.yml', $id)))) {
			$this->debugmessage(sprintf("Game data (%s) loaded from cache", $id), 'info');
			list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)];
		} else { //Load game data & platform class from yml
			list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)] = yaml_parse_file(sprintf('games/%s.yml', $id), -1);
			$this->cache[sprintf('MPASM.ymlmodified.%s', $id)] = filemtime(sprintf('games/%s.yml', $id));
		}
		return array($game,$addresses);
	}
	public function decimal_to_function($input) {
		return (isset($this->addresses[$input]['name']) && ($this->addresses[$input]['name'] != "")) ? $this->addresses[$input]['name'] : sprintf(core::addressformat, $input);
	}
	function debugvar($var, $label) {
		if (isset($this->settings['debug']) && $this->settings['debug'])
			display::debugvar($var, $label);
	}
	function debugmessage($msg, $level = 'error') {
		if (isset($this->settings['debug']) && $this->settings['debug'])
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
