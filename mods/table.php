<?php
class table {
	private $main;
	
	function __construct() {
		$this->main = Main::get();
	}
	public function execute() {
		$realoffset = $this->main->platform->map_rom($this->main->offset);
		$this->main->rom->seekTo($realoffset);
		$table = $this->main->addresses[$this->main->offset];

		$this->main->dataname = sprintf(core::addressformat, $this->main->offset);
		if (isset($table['description']))
			$this->main->dataname = $table['description'];
			
		
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
		return array('entries' => $entries);
	}
	public static function shouldhandle() {
		if (isset(Main::get()->addresses[Main::get()->offset]['type']) && (Main::get()->addresses[Main::get()->offset]['type'] === 'data') && isset(Main::get()->addresses[Main::get()->offset]['entries']))
			return true;
		return false;
	}
	private function process_entries(&$offset, $end, $entries, $offsetkeys = true) {
		$output = array();
		$offsets = array();
		if ($this->main->rom->currentoffset() != $this->main->platform->map_rom($offset))
			throw new Exception(sprintf('Offset mismatch! %X != %X', $this->main->rom->currentoffset(), $this->main->platform->map_rom($offset)));
		$i = 0;
		while ($offset < $end) {
			$tmpoffset = $offset;
			$tmparray = array();
			foreach ($entries as $entry) {
				if ($i++ > 0x10000)
					break 2;
				eval('$entry[\'size\'] = '.str_replace(array_keys($tmparray), $tmparray, $entry['size']).';');
				$bytesread = isset($entry['size']) ? $entry['size'] : 0;
				if (!isset($entry['type']) || ($entry['type'] == 'int')) {
					$num = $this->main->rom->read_varint($entry['size']);
					if (isset($entry['values'][$num]))
						$tmparray[$entry['name']] = $entry['values'][$num];
					else if (isset($entry['signed']) && ($entry['signed'] == true))
						$tmparray[$entry['name']] = uint($num, $entry['size']*8);
					else
						$tmparray[$entry['name']] = $num;
				}
				else if ($entry['type'] == 'bitfield')
					$tmparray[$entry['name']] = $this->main->rom->read_bit_field($entry['size'],$entry['bitvalues']);
				else if ($entry['type'] == 'hexint')
					$tmparray[$entry['name']] = str_pad(strtoupper(dechex($this->main->rom->read_varint($entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
				else if ($entry['type'] == 'pointer')
					$tmparray[$entry['name']] = $this->read_pointer($entry['size']);
				else if ($entry['type'] == 'palette')
					if (isset($this->main->opts['yaml']))
						$tmparray[$entry['name']] = $this->main->rom->read_palette($entry['size']);
					else
						$tmparray[$entry['name']] = asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', $this->main->rom->read_palette($entry['size']));
				else if ($entry['type'] == 'binary')
					$tmparray[$entry['name']] = decbin($this->main->rom->read_varint($entry['size']));
				else if ($entry['type'] == 'boolean')
					$tmparray[$entry['name']] = $this->main->rom->read_varint($entry['size']) ? true : false;
				else if ($entry['type'] == 'tile')
					$tmparray[$entry['name']] = $this->main->rom->read_tile($entry['bpp'], isset($entry['palette']) ? $entry['palette'] : -1, !isset($this->main->opts['yaml']));
				else if (isset($this->main->game['texttables'][$entry['type']]))
					$tmparray[$entry['name']] = trim($this->main->rom->read_string($bytesread, $this->main->game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null));
				else if ($entry['type'] == 'asciitext')
					$tmparray[$entry['name']] = $this->main->rom->read_string($bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'UTF-16')
					$tmparray[$entry['name']] = $this->main->rom->read_string($bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'table') {
					$tmparray[$entry['name']] = $this->process_entries($offset, $offset+$entry['size'], $entry['entries'], false);
					$bytesread = 0;
				} else
					$tmparray[$entry['name']] = $this->main->rom->read_bytes($entry['size']);
				$offset += $bytesread;
			}
			if ($offsetkeys)
				$output[$tmpoffset] = $tmparray;
			else 
				$output[] = $tmparray;
		}
		return $output;
	}
	private function read_pointer($size) {
		$offset = $this->main->rom->read_varint($size);
		$datablock = Main::get()->getPreviousOffset($offset);
		if ($datablock == -1)
			return sprintf(core::addressformat, $offset);
		if ($datablock != $offset)
			return sprintf('%s+%d ('.core::addressformat.')', $this->main->decimal_to_function($datablock), $offset-$datablock, $offset);
		return $this->main->decimal_to_function($datablock);
	}
}
?>