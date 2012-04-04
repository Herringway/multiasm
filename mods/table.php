<?php
class table {
	public function execute() {
		$realoffset = platform::get()->map_rom(Main::get()->offset);
		rom::get()->seekTo($realoffset);
		$table = Main::get()->addresses[Main::get()->offset];

		Main::get()->dataname = sprintf(core::addressformat, Main::get()->offset);
		if (isset($table['description']))
			Main::get()->dataname = $table['description'];
			
		
		$entries = $this->process_entries(Main::get()->offset, Main::get()->offset+$table['size'], $table['entries'], true, isset($table['terminator']) ? $table['terminator'] : null);
		
		Main::get()->nextoffset = Main::get()->decimal_to_function(Main::get()->offset);
		$i = 0;
		foreach ($entries as $k => $item)
			if (isset($item['Name']) && (trim($item['Name']) !== ''))
				Main::get()->menuitems[sprintf(core::addressformat, $k)] = trim($item['Name']);
			else
				Main::get()->menuitems[sprintf(core::addressformat, $k)] = sprintf(core::addressformat.' (%04X)', $k, $i++);
		return array($table['entries'], $entries);
	}
	public static function shouldhandle() {
		if (isset(Main::get()->addresses[Main::get()->offset]['type']) && (Main::get()->addresses[Main::get()->offset]['type'] === 'data') && isset(Main::get()->addresses[Main::get()->offset]['entries']))
			return true;
		return false;
	}
	private function process_entries(&$offset, $end, $entries, $offsetkeys = true, $terminator = null) {
		if ($terminator != null)
			$end = $offset + 0x10000;
		$output = array();
		$offsets = array();
		if (rom::get()->currentoffset() != platform::get()->map_rom($offset))
			throw new Exception(sprintf('Offset mismatch! %X != %X', rom::get()->currentoffset(), platform::get()->map_rom($offset)));
		$i = 0;
		while ($offset < $end) {
			$tmpoffset = $offset;
			$tmparray = array();
			$ints = array();
			foreach ($entries as $entry) {
				if ($i++ > 0x10000)
					break 2;
				if (isset($entry['size']))
					$entry['size'] = eval('return '.str_replace(array_keys($ints), $ints, $entry['size']).';');
				$bytesread = isset($entry['size']) ? $entry['size'] : 0;
				if (!isset($entry['type']) || ($entry['type'] == 'int')) {
					$num = rom::get()->read_varint($entry['size']);
					$ints[$entry['name']] = $num;
					if (isset($entry['values'][$num]))
						$tmparray[$entry['name']] = $entry['values'][$num];
					else if (isset($entry['signed']) && ($entry['signed'] == true))
						$tmparray[$entry['name']] = uint($num, $entry['size']*8);
					else
						$tmparray[$entry['name']] = $num;
				}
				else if ($entry['type'] == 'bitfield')
					$tmparray[$entry['name']] = rom::get()->read_bit_field($entry['size'],$entry['bitvalues']);
				else if ($entry['type'] == 'hexint')
					$tmparray[$entry['name']] = str_pad(strtoupper(dechex(rom::get()->read_varint($entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
				else if ($entry['type'] == 'pointer')
					if (isset(Main::get()->opts['yaml']))
						$tmparray[$entry['name']] = $this->read_pointer($entry['size'], false, isset($entry['reverseendian']));
					else
						$tmparray[$entry['name']] = $this->read_pointer($entry['size'], true, isset($entry['reverseendian']));
				else if ($entry['type'] == 'palette')
					if (isset(Main::get()->opts['yaml']))
						$tmparray[$entry['name']] = rom::get()->read_palette($entry['size']);
					else
						$tmparray[$entry['name']] = asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', rom::get()->read_palette($entry['size']));
				else if ($entry['type'] == 'binary')
					$tmparray[$entry['name']] = decbin(rom::get()->read_varint($entry['size']));
				else if ($entry['type'] == 'boolean')
					$tmparray[$entry['name']] = rom::get()->read_varint($entry['size']) ? true : false;
				else if ($entry['type'] == 'tile')
					$tmparray[$entry['name']] = rom::get()->read_tile($entry['bpp'], isset($entry['palette']) ? $entry['palette'] : -1, !isset(Main::get()->opts['yaml']));
				else if (isset(Main::get()->game['texttables'][$entry['type']]))
					$tmparray[$entry['name']] = trim(rom::get()->read_string($bytesread, Main::get()->game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null));
				else if ($entry['type'] == 'asciitext')
					$tmparray[$entry['name']] = rom::get()->read_string($bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'UTF-16')
					$tmparray[$entry['name']] = rom::get()->read_string($bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
				else if ($entry['type'] == 'table') {
					$tmparray[$entry['name']] = $this->process_entries($offset, $offset+(isset($entry['size']) ? $entry['size'] : 0), $entry['entries'], false, isset($entry['terminator']) ? $entry['terminator'] : null);
					$bytesread = 0;
				} else
					$tmparray[$entry['name']] = rom::get()->read_bytes($entry['size']);
				$offset += $bytesread;
				if ($tmparray[$entry['name']] === $terminator) {
					Main::get()->debugvar($tmparray[$entry['name']], 'val');
					Main::get()->debugvar($terminator, 'terminator');
					break 2;
				}
			}
			if ($offsetkeys)
				$output[$tmpoffset] = $tmparray;
			else 
				$output[] = $tmparray;
		}
		return $output;
	}
	private function read_pointer($size, $html = false, $revorder = false) {
		$offset = rom::get()->read_varint($size, -1, $revorder);
		if (!platform::get()->isROM($offset))
			return sprintf(core::addressformat, $offset);
		$datablock = Main::get()->getDataBlock($offset);
		if ($datablock == -1)
			return sprintf(core::addressformat, $offset);
		if (!$html) {
			if ($datablock != $offset)
				return sprintf('%s+%d ('.core::addressformat.')', Main::get()->decimal_to_function($datablock), $offset-$datablock, $offset);
			return Main::get()->decimal_to_function($datablock);
		} else {
			if ($datablock != $offset)
				return sprintf('<a href="%s#%3$X">%1$s+%2$d (%3$X)</a>', Main::get()->decimal_to_function($datablock), $offset-$datablock, $offset);
			return sprintf('<a href="%s">%1$s</a>', Main::get()->decimal_to_function($datablock));
		}
	}
}
?>