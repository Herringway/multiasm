<?php
class table {
	private $main;
	
	function __construct(&$main) {
		$this->main = $main;
	}
	public function execute() {
		$realoffset = $this->main->platform->map_rom($this->main->offset);
		fseek($this->main->gamehandle, $realoffset);
		$table = $this->main->addresses[$this->main->offset];

		$this->main->dataname = sprintf(core::addressformat, $this->main->offset);
		if (isset($table['description']))
			$this->main->dataname = $table['description'];
			
		$header = array();
		
		if (isset($table['header'])) {
			$header = $this->process_entries($this->main->offset, $this->main->offset+1, $table['header']);
			$this->main->yamldata[] = $table['header'];
			$this->main->yamldata[] = $header;
			$this->main->menuitems['header'] = 'Header';
		}
		
		$entries = $this->process_entries($this->main->offset, $this->main->offset+$table['size'], $table['entries']);
		
		$this->main->nextoffset = $this->main->decimal_to_function($this->main->offset);
		$this->main->yamldata[] = $table['entries'];
		$this->main->yamldata[] = $entries;
		$i = 0;
		foreach ($entries as $k => $item)
			if (isset($item['Name']) && (trim($item['Name']) !== ''))
				$this->main->menuitems[sprintf(core::addressformat, $k)] = trim($item['Name']);
			else
				$this->main->menuitems[sprintf(core::addressformat, $k)] = sprintf(core::addressformat.' (%04X)', $k, $i++);
		return array('header' => $header,'entries' => $entries);
	}
	public static function shouldhandle($main) {
		if (isset($main->addresses[$main->offset]['type']) && ($main->addresses[$main->offset]['type'] === 'data') && isset($main->addresses[$main->offset]['entries']))
			return true;
		return false;
	}
	private function process_entries(&$offset, $end, $entries) {
		$output = array();
		$offsets = array();
		if (ftell($this->main->gamehandle) != $this->main->platform->map_rom($offset))
			throw new Exception(sprintf('Offset mismatch! %X != %X', ftell($this->main->gamehandle),$this->main->platform->map_rom($offset)));
		$i = 0;
		while ($offset < $end) {
			$tmpoffset = $offset;
			$tmparray = array();
			foreach ($entries as $entry) {
				if ($i++ > 0x10000)
					break 2;
				$this->main->debugmessage(sprintf('Found %s at offset %X', isset($entry['type']) ? $entry['type'] : 'int', ftell($this->main->gamehandle)), 'info');
				$bytesread = isset($entry['size']) ? $entry['size'] : 0;
				if (!isset($entry['type']) || ($entry['type'] == 'int')) {
					$num = read_int($this->main->gamehandle, $entry['size']);
					if (isset($entry['values'][$num]))
						$tmparray[$entry['name']] = $entry['values'][$num];
					else if (isset($entry['bitvalues']))
						$tmparray[$entry['name']] = get_bit_flags2($num,$entry['bitvalues']);
					else if (isset($entry['signed']) && ($entry['signed'] == true))
						$tmparray[$entry['name']] = uint($num, $entry['size']*8);
					else
						$tmparray[$entry['name']] = $num;
				}
				else if ($entry['type'] == 'hexint')
					$tmparray[$entry['name']] = str_pad(strtoupper(dechex(read_int($this->main->gamehandle, $entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
				else if ($entry['type'] == 'pointer')
					$tmparray[$entry['name']] = $this->read_pointer($entry['size']);
				else if ($entry['type'] == 'palette')
					$tmparray[$entry['name']] = asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', read_palette($this->main->gamehandle, $entry['size']));
				else if ($entry['type'] == 'binary')
					$tmparray[$entry['name']] = decbin(read_int($this->main->gamehandle, $entry['size']));
				else if ($entry['type'] == 'boolean')
					$tmparray[$entry['name']] = read_int($this->main->gamehandle, $entry['size']) ? true : false;
				else if ($entry['type'] == 'tile')
					$tmparray[$entry['name']] = read_tile($this->main->gamehandle, $entry['bpp']);
				else if (isset($this->main->game['texttables'][$entry['type']]))
					$tmparray[$entry['name']] = read_string($this->main->gamehandle, $bytesread, $this->main->game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'asciitext')
					$tmparray[$entry['name']] = read_string($this->main->gamehandle, $bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'UTF-16')
					$tmparray[$entry['name']] = read_string($this->main->gamehandle, $bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
				else
					$tmparray[$entry['name']] = read_bytes($this->main->gamehandle, $entry['size']);
				$offset += $bytesread;
			}
			$output[$tmpoffset] = $tmparray;
		}
		return $output;
	}
	private function read_pointer($size) {
		return $this->main->decimal_to_function(read_int($this->main->gamehandle, $size));
	}
}
?>