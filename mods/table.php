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

		$initialoffset = $this->main->offset;

		$tmparray = array();
		$output = array();
		$i = 0;
		$this->main->dataname = sprintf(core::addressformat, $this->main->offset);
		if (isset($table['description']))
			$this->main->dataname = $table['description'];
		$header = array();
		$headerend = $this->main->offset;
		if (isset($table['header']))
			list($header, $headerend) = $this->process_entries($offset, $initialoffset+1, $table['header']);
		list($entries,$offsets,$offset) = $this->process_entries($headerend, $initialoffset+$table['size'], $table['entries']);
		$this->main->nextoffset = $this->main->decimal_to_function($offset);
		$this->main->yamldata[] = $table['entries'];
		$this->main->yamldata[] = $entries;
		$i = 0;
		foreach ($entries as $k => $item)
			if (isset($item['Name']) && (trim($item['Name']) !== ''))
				$this->main->menuitems[sprintf(core::addressformat, $offsets[$k])] = trim($item['Name']);
			else
				$this->main->menuitems[sprintf(core::addressformat, $offsets[$k])] = sprintf(core::addressformat.' (%04X)', $offsets[$k], $i++);
		return array('header' => $header,'entries' => $entries, 'offsets' => $offsets);
	}
	public static function shouldhandle($main) {
		if (isset($main->addresses[$main->offset]['type']) && ($main->addresses[$main->offset]['type'] === 'data') && isset($main->addresses[$main->offset]['entries']))
			return true;
		return false;
	}
	private function process_entries($offset, $end, $entries) {
		$output = array();
		$offsets = array();
		while ($offset < $end) {
			$tmpoffset = $offset;
			$tmparray = array();
			foreach ($entries as $entry) {
				$bytesread = isset($entry['size']) ? $entry['size'] : 0;
				if (!isset($entry['type']) || ($entry['type'] == 'int')) {
					$num = read_int($this->main->gamehandle, $entry['size']);
					if (isset($entry['values'][$num]))
						$tmparray[$entry['name']] = $entry['values'][$num];
					else if (isset($entry['bitvalues']))
						$tmparray[$entry['name']] = get_bit_flags2($num,$entry['bitvalues']);
					else
						$tmparray[$entry['name']] = $num;
				}
				else if ($entry['type'] == 'hexint')
					$tmparray[$entry['name']] = str_pad(strtoupper(dechex(read_int($this->main->gamehandle, $entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
				else if ($entry['type'] == 'pointer')
					$tmparray[$entry['name']] = strtoupper(dechex(read_int($this->main->gamehandle, $entry['size'])));
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
			$output[] = $tmparray;
			$offsets[] = $tmpoffset;
		}
		return array($output, $offsets, $offset);
	}
}
?>