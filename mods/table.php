<?php
class table {
	private $pointerblocks = array();
	public function execute() {
		$realoffset = platform::get()->map_rom(Main::get()->offset);
		rom::get()->seekTo($realoffset);
		$table = Main::get()->addresses[Main::get()->offset];

		Main::get()->dataname = sprintf(core::addressformat, Main::get()->offset);
		if (isset($table['description']))
			Main::get()->dataname = $table['description'];
			
		
		$entries = $this->read_table(Main::get()->offset, Main::get()->offset+$table['size'], $table['entries'], true, isset($table['terminator']) ? $table['terminator'] : null);
		
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
	private function getValue($type, $entry, &$offset, &$bytesread) {
		switch ($type) {
			case 'int':
				$num = rom::get()->read_varint($entry['size']);
				if (isset($entry['values'][$num]))
					return $entry['values'][$num];
				else if (isset($entry['signed']) && ($entry['signed'] == true))
					return uint($num, $entry['size']*8);
				else
					return $num;
			break;
			case 'bitfield':
				return rom::get()->read_bit_field($entry['size'],$entry['bitvalues']);
			break;
			case 'hexint':
				return str_pad(strtoupper(dechex(rom::get()->read_varint($entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
			case 'pointer':
				if (isset(Main::get()->opts['yaml']))
					return $this->read_pointer($entry['size'], false, isset($entry['endianness']) ? $entry['endianness'] : null,  isset($entry['base']) ? $entry['base'] : null);
				else
					return $this->read_pointer($entry['size'], true, isset($entry['endianness']) ? $entry['endianness'] : null, isset($entry['base']) ? $entry['base'] : null);
			break;
			case 'palette':
				if (isset(Main::get()->opts['yaml']))
					return rom::get()->read_palette($entry['size']);
				else
					return asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', rom::get()->read_palette($entry['size']));
			break;
			case 'binary':
				return decbin(rom::get()->read_varint($entry['size']));
			break;
			case 'boolean':
				return rom::get()->read_varint($entry['size']) ? true : false;
			break;
			case 'tile':
				return rom::get()->read_tile($entry['bpp'], isset($entry['palette']) ? $entry['palette'] : -1, !isset(Main::get()->opts['yaml']));
			break;
			case 'asciitext':
				return rom::get()->read_string($bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'UTF-16':
				return rom::get()->read_string($bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'table':
				$bytesread = 0;
				return $this->read_table($offset, $offset+(isset($entry['size']) ? $entry['size'] : 0), $entry['entries'], false, isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'bytearray':
				return rom::get()->read_bytes($entry['size']);
			break;
			default:
				if (isset(Main::get()->game['texttables'][$entry['type']]))
					return trim(rom::get()->read_string($bytesread, Main::get()->game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null, isset(Main::get()->opts['hideccs'])));
			break;
		}
		return $value;
	}
	private function read_table(&$offset, $end, $entries, $offsetkeys = true, $terminator = null) {
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
				
				$value = $this->getValue(isset($entry['type']) ? $entry['type'] : 'int', $entry, $offset, $bytesread);
				
				$offset += $bytesread;
				if (isset($entry['name'])) {
					if (!isset($entry['type']) || ($entry['type'] == 'int'))
						$ints[$entry['name']] = $value;
					$tmparray[$entry['name']] = $value;
					
				} else {
					$tmparray[] = $value;
				}
				if ($value === $terminator) {
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
	private function read_pointer($size, $html = false, $endianness = null, $base = null) {
		$offset = rom::get()->read_varint($size, -1, $endianness) + (($base != null) ? $base : 0);
		if (isset($this->pointerblocks[$offset]))
			return $this->pointerblocks[$offset];
		if (!platform::get()->isROM($offset))
			return $this->pointerblocks[$offset] = sprintf(core::addressformat, $offset);
		$datablock = Main::get()->getDataBlock($offset);
		if ($datablock == -1)
			return $this->pointerblocks[$offset] = sprintf(core::addressformat, $offset);
		if (!$html) {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('%s+%d ('.core::addressformat.')', Main::get()->decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = Main::get()->decimal_to_function($datablock);
		} else {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('<a href="%s#%3$X">%1$s+%2$d (%3$X)</a>', Main::get()->decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = sprintf('<a href="%s">%1$s</a>', Main::get()->decimal_to_function($datablock));
		}
	}
}
?>