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
	public function execute() {
		require_once 'commonfunctions.php';
		$this->settings = load_settings();
		$this->debugvar($this->settings, 'settings');
		$this->debugvar($_SERVER, 'Server');
		
		if (PHP_SAPI === 'cli')
			require 'cli.php';
		else
			require 'web.php';
		$display = new display($this);
		$argv = $display->getArgv();
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
		
		//Where are we?
		$this->offset = $this->core->getDefault();
		$this->offsetname = isset($this->addresses[$this->offset]['name']) ? $this->addresses[$this->offset]['name'] : '';
		if (isset($argv[1]) && ($argv[1] != null)) {
			if (in_array($argv[1], array('rommap', 'stats', 'issues')))
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
		//What are we doing? (should this be modularized? I think so)
		switch ($this->offset) {
			case 'rommap':
				$output = $this->prepare_rommap();
				$display->mode = 'rommap'; break;
			case 'stats':
				$output = $this->prepare_stats();
				$display->mode = 'stats'; break;
			case 'issues':
				$output = $this->prepare_issues();
				$display->mode = 'issues'; break;
			default:
				if (isset($this->addresses[$this->offset]['type']) && ($this->addresses[$this->offset]['type'] == 'data')) {
					if (isset($this->addresses[$this->offset]['entries'])) {
						$output = $this->prepare_table();
						$display->mode = 'table';
					} else {
						$output = $this->prepare_hexdump();
						$display->mode = 'hex';
					}
				} else {
					$output = $this->prepare_asm();
					$display->mode = core::template;
				}
				break;
		}
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
	function prepare_issues() {
		$allproblems = array();
		$prev = 0;
		foreach ($this->addresses as $offset => $entry) {
			if ($offset < $this->platform->base())
				continue;
			if (isset($entry['ignore']) && ($entry['ignore'] == true))
				continue;
			$problems = array();
			if ($prev > $offset)
				$problems[] = 'Overlap detected';
			if (!isset($entry['size']))
				$problems[] = 'No size defined';
			if (($offset >= $this->platform->base()) && !isset($entry['type']))
				$problems[] = 'No type defined';
			if (!isset($entry['name']) && (!isset($entry['type']) || ($entry['type'] != 'nullspace')))
				$problems[] = 'No name defined';
			if (!isset($entry['description']) && (!isset($entry['type']) || ($entry['type'] != 'nullspace')))
				$problems[] = 'No description defined';
			if (isset($entry['size']) && !isset($this->addresses[$offset+$entry['size']]) && ($offset+$entry['size'] < $this->opts['rombase']+$this->game['size']))
				$allproblems[$this->decimal_to_function($offset+$entry['size'])] = array('Undefined area!');
			if ($problems != array())
				$allproblems[$this->decimal_to_function($offset)] = $problems;
			$prev = $offset+(isset($entry['size']) ? $entry['size'] : 0);
		}
		return $allproblems;
	}
	function prepare_stats() {
		$stats = array();
		$counteddata = 0;
		$biggest = array('size' => 0, 'offset' => 0);
		$biggestroutine = array('size' => 0, 'offset' => 0);
		$divisions = array();
		$routines = array();
		foreach ($this->addresses as $k => $entry) {
			if ($k < $this->opts['rombase'])
				continue;
			if (isset($entry['ignore']) && ($entry['ignore']))
				continue;
			if (isset($entry['size'])) {
				if (!isset($entry['type']))
					$entry['type'] = 'Unknown';
				if (!isset($divisions[$entry['type']]))
					$divisions[$entry['type']] = 0;
				$divisions[$entry['type']] += $entry['size'];
				if (($entry['type'] == 'assembly') && isset($entry['name']))
					$routines[] = $entry['name'];
				if ($entry['size'] > $biggest['size'])
					$biggest = array('size' => $entry['size'], 'name' => !empty($entry['name']) ? $entry['name'] : sprintf('%06X', $k));
				if (($entry['size'] > $biggestroutine['size']) && isset($entry['type']) && ($entry['type'] == 'assembly'))
					$biggestroutine = array('size' => $entry['size'], 'name' => !empty($entry['name']) ? $entry['name'] : sprintf('%06X', $k));
			}
			if (($k >= $this->opts['rombase']) && (isset($entry['size'])))
				$counteddata += $entry['size'];
		}
		$stats['Known_Data'] = $counteddata;
		$stats['Biggest'] = $biggest;
		$stats['Biggest_Routine'] = $biggestroutine;
		if ($counteddata < $this->game['size'])
			$divisions['Unknown'] = $this->game['size'] - $counteddata;
		$stats['Size'] = $divisions;
		$this->yamldata[] = $stats;
		return $stats;
	}
	function prepare_rommap() {
		$output = array();
		foreach ($this->addresses as $addr=>$data) {
			try {
				$realaddr = $this->platform->map_rom($addr);
				if ($realaddr !== null)
					$output[] = array('address' => isset($this->opts['real_address']) ? $realaddr : $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		$this->yamldata[] = $output;
		return $output;
	}
	function prepare_asm() {
		$output = $this->core->execute($this->offset,$this->offsetname);
		$this->nextoffset = $this->decimal_to_function($this->core->currentoffset);

		if (isset($this->addresses[$this->core->initialoffset]['description']))
			$this->dataname = $this->addresses[$this->core->initialoffset]['description'];
		else
			$this->dataname = sprintf('%X', $this->core->initialoffset);
		if (isset($this->addresses[$this->core->initialoffset]['arguments']))
			$arguments = $this->addresses[$this->core->initialoffset]['arguments'];
			
		$branches = array();
		if (isset($this->addresses[$this->core->initialoffset]['labels']))
			$branches = $this->addresses[$this->core->initialoffset]['labels'];
		//Saves a stub to the relevant YAML file. Work on this later.
		/*if (isset($game['genstub'])) {
			$branches = null;
			if (isset($known_addresses_p[$core->initialoffset]['labels']))
				$branches = $known_addresses_p[$core->initialoffset]['labels'];
			if ($core->branches !== null) {
				ksort($core->branches);
				$unknownbranches = 0;
				foreach ($core->branches as $k=>$branch)
					$branches[$k] = 'UNKNOWN'.$unknownbranches++;
			}
			if (!isset($known_addresses_p[$core->initialoffset]['name']))
				$known_addresses_p[$core->initialoffset]['name'] = '';
			if (!isset($known_addresses_p[$core->initialoffset]['description']))
				$known_addresses_p[$core->initialoffset]['description'] = '';
			$known_addresses_p[$core->initialoffset]['type'] = 'assembly';
			$known_addresses_p[$core->initialoffset]['size'] = $core->currentoffset-$core->initialoffset;
			foreach ($core->getMisc() as $k=>$val)
				$known_addresses_p[$core->initialoffset][$k] = $val;
			$known_addresses_p[$core->initialoffset]['labels'] = $branches;
			ksort($known_addresses_p);
			$output = preg_replace_callback('/ ?(\d+):/', 'hexafixer', yaml_emit($gameorig).yaml_emit($known_addresses_p));
			file_put_contents('games/'.$settings['gameid'].'.yml', $output);
		}*/
		$this->yamldata[] = $output;
		return $output;
	}
	function prepare_table() {
		$realoffset = $this->platform->map_rom($this->offset);
		fseek($this->gamehandle, $realoffset);
		$table = $this->addresses[$this->offset];

		$initialoffset = $this->offset;

		$tmparray = array();
		$output = array();
		$i = 0;
		$this->dataname = sprintf(core::addressformat, $this->offset);
		if (isset($table['description']))
			$this->dataname = $table['description'];
		$header = array();
		$headerend = $this->offset;
		if (isset($table['header']))
			list($header, $headerend) = $this->process_entries($offset, $initialoffset+1, $table['header']);
		list($entries,$offsets,$offset) = $this->process_entries($headerend, $initialoffset+$table['size'], $table['entries']);
		$this->nextoffset = $this->decimal_to_function($offset);
		$this->yamldata[] = $table['entries'];
		$this->yamldata[] = $entries;
		return array('header' => $header,'entries' => $entries, 'offsets' => $offsets);
	}
	private function decimal_to_function($input) {
		return isset($this->addresses[$input]['name']) ? $this->addresses[$input]['name'] : sprintf(core::addressformat, $input);
	}
	function prepare_hexdump() {
		if (!isset($this->addresses[$this->offset]['size']))
			die('Table has no size defined!');
		require_once '../hexview.php';
		fseek($this->gamehandle, $this->platform->map_rom($this->offset));
		$data = fread($this->gamehandle, $this->addresses[$this->offset]['size']);
		$this->nextoffset = $this->decimal_to_function($this->offset+$this->addresses[$this->offset]['size']);
		if (isset($this->addresses[$this->offset]['charset']))
			$charset = $game['texttables'][$this->addresses[$this->offset]['charset']]['replacements'];
		else if (isset($game['defaulttext']))
			$charset = $game['texttables'][$game['defaulttext']]['replacements'];
		else
			$charset = null;
		return hexview($data, isset($this->addresses[$this->offset]['width']) ? $this->addresses[$this->offset]['width'] : 16, $this->offset, $charset);
	}
	function process_entries($offset, $end, $entries) {
		$output = array();
		$offsets = array();
		while ($offset < $end) {
			$tmpoffset = $offset;
			foreach ($entries as $entry) {
				$bytesread = isset($entry['size']) ? $entry['size'] : 0;
				if (!isset($entry['type']) || ($entry['type'] == 'int')) {
					$num = read_int($this->gamehandle, $entry['size']);
					if (isset($entry['values'][$num]))
						$tmparray[$entry['name']] = $entry['values'][$num];
					else if (isset($entry['bitvalues']))
						$tmparray[$entry['name']] = get_bit_flags2($num,$entry['bitvalues']);
					else
						$tmparray[$entry['name']] = $num;
				}
				else if ($entry['type'] == 'hexint')
					$tmparray[$entry['name']] = str_pad(strtoupper(dechex(read_int($this->gamehandle, $entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
				else if ($entry['type'] == 'pointer')
					$tmparray[$entry['name']] = strtoupper(dechex(read_int($this->gamehandle, $entry['size'])));
				else if ($entry['type'] == 'palette')
					$tmparray[$entry['name']] = asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', read_palette($this->gamehandle, $entry['size']));
				else if ($entry['type'] == 'binary')
					$tmparray[$entry['name']] = decbin(read_int($this->gamehandle, $entry['size']));
				else if ($entry['type'] == 'boolean')
					$tmparray[$entry['name']] = read_int($this->gamehandle, $entry['size']) ? true : false;
				else if ($entry['type'] == 'tile')
					$tmparray[$entry['name']] = read_tile($this->gamehandle, $entry['bpp']);
				else if (isset($this->game['texttables'][$entry['type']]))
					$tmparray[$entry['name']] = read_string($this->gamehandle, $bytesread, $this->game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'asciitext')
					$tmparray[$entry['name']] = read_string($this->gamehandle, $bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'UTF-16')
					$tmparray[$entry['name']] = read_string($this->gamehandle, $bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
				else
					$tmparray[$entry['name']] = read_bytes($this->gamehandle, $entry['size']);
				$offset += $bytesread;
			}
			$output[] = $tmparray;
			$offsets[] = $tmpoffset;
			$tmparray = array();
		}
		return array($output, $offsets, $offset);
	}
	function debugvar($var, $label) {
		if (isset($this->settings['debug']) && $this->settings['debug']) {
			if (PHP_SAPI === 'cli') {
				echo $label.': '; var_dump($var);
			} else 
				ChromePhp::log($label, $var);
		}
	}
}

$singleton = new Backend();
$singleton->execute();
?>
