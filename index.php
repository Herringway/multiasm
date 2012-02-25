<?php
require_once 'chromephp.php';
class Backend {
	public $settings;
	public $platform;
	public $core;
	public $game;
	public $gameid;
	public $gamehandle;
	public $addresses;
	public $offset;
	public $offsetname;
	public $opts;
	public $nextoffset;
	public $yamldata;
	public $dataname;
	public $menuitems = array();
	
	public function execute() {
		require_once 'commonfunctions.php';
		$this->settings = load_settings();
		
		if (PHP_SAPI === 'cli')
			require 'cli.php';
		else
			require 'web.php';
		$display = new display($this);
		$argv = $display->getArgv();
		$this->debugvar($this->settings, 'settings');
		$this->debugvar($_SERVER, 'Server');
		$this->debugvar($argv, 'args');
		
		//Options!
		$this->opts = $display->getOpts($argv);
		$this->debugvar($this->opts, 'options');
		
		//Determine which game to work with
		if (isset($argv[0]) && ($argv[0] != null) && file_exists(sprintf('games/%s.yml', $argv[0])))
			$this->gameid = $argv[0];
		else
			$this->gameid = $this->settings['gameid'];
			
		//Load game data & platform class
		list($this->game,$this->addresses) = yaml_parse_file(sprintf('games/%s.yml', $this->gameid), -1);
		
		require_once sprintf('platforms/%s.php', $this->game['platform']);
		
		$this->game['size'] = filesize($this->settings['rompath'].$this->gameid.'.'.platform::extension);
		
		$this->gamehandle = fopen($this->settings['rompath'].$this->gameid.'.'.platform::extension, 'r');
		$this->platform = new platform($this);
		
		$this->opts['rombase'] = $this->platform->base();
		
		$this->debugvar($this->offset, 'Location');
		$this->debugvar($this->offsetname, 'Location_Fancy');
		
		//Load CPU Class
		
		$cpu = $this->game['processor'];
		if (isset($known_addresses[$this->offset]['cpu'])) 
			$cpu = $this->addresses[$this->offset]['cpu']; //Override if game data sez so
		require_once sprintf('cpus/%s.php', $cpu);
		$this->core = new core($this);
		
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
		$this->offsetname = isset($this->addresses[$this->offset]['name']) ? $this->addresses[$this->offset]['name'] : '';
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], $magicvalues))
				$this->offset = $argv[1];
			else {
				$this->offsetname = $argv[1];
				$this->offset = hexdec($argv[1]);
				if (strtoupper(dechex(hexdec($argv[1]))) != strtoupper($argv[1])) {
					foreach ($this->addresses as $k => $addr)
						if (isset($addr['name']) && ($addr['name'] == $argv[1])) {
							$this->offset = $k;
							$this->offsetname = $argv[1];
							break;
						}
				}
			}
		}
		//What are we doing?
		if (in_array($this->offset, $magicvalues))
			$modname = $this->offset;
		else
			foreach ($othermods as $mod)
				if ($mod::shouldhandle($this))
					$modname = $mod;
		$module = new $modname($this);
		$output = $module->execute();
		$display->mode = $modname;
		if (method_exists($module, 'getTemplate'))
			$display->mode = $module->getTemplate();
			
		//Display stuff
		if (isset($this->opts['yaml'])) {
			header('Content-Type: text/plain; charset=UTF-8');
			if ($this->yamldata !== null)
				foreach ($this->yamldata as $yamldoc)
					echo yaml_emit($yamldoc);
		} else {	
			$display->display($output);
		}
	}
	public function decimal_to_function($input) {
		return isset($this->addresses[$input]['name']) ? $this->addresses[$input]['name'] : sprintf(core::addressformat, $input);
	}
	function debugvar($var, $label) {
		if (isset($this->settings['debug']) && $this->settings['debug'])
			display::debugvar($var, $label);
	}
}

$singleton = new Backend();
$singleton->execute();
?>
