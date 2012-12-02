<?php
class table extends gamemod {
	private $offset;
	private $pointerblocks = array();
	public function execute($arg) {
		$this->offset = $arg;
		global $addresses, $metadata;
		$table = $addresses[$this->offset];
		
		$entries = $this->read_table($this->offset, $this->offset+$table['size'], $table['entries'], true, isset($table['terminator']) ? $table['terminator'] : null);
		
		$metadata['nextoffset'] = decimal_to_function($this->offset);
		$i = 0;
		foreach ($entries as $k => $item)
			if (isset($item['Name']) && (trim($item['Name']) !== ''))
				$metadata['menuitems'][sprintf(core::addressformat, $k)] = trim($item['Name']);
			else
				$metadata['menuitems'][sprintf(core::addressformat, $k)] = sprintf(core::addressformat.' (%04X)', $k, $i++);
		return array($table['entries'], $entries);
	}
	public function description() {
		return getDescription($this->offset);
	}
	public static function shouldhandle($offset) {
		global $addresses;
		if (isset($addresses[$offset]['type']) && ($addresses[$offset]['type'] === 'data') && isset($addresses[$offset]['entries']))
			return true;
		return false;
	}
	private function getValue($type, $entry, &$offset, &$bytesread) {
		global $rom, $opts, $game, $format;
		switch ($type) {
			case 'int':
				$num = $rom->read_varint($entry['size']);
				if (isset($entry['values'][$num]))
					return $entry['values'][$num];
				else if (isset($entry['signed']) && ($entry['signed'] == true))
					return uint($num, $entry['size']*8);
				else
					return $num;
			break;
			case 'bitfield':
				return $rom->read_bit_field($entry['size'],$entry['bitvalues']);
			break;
			case 'hexint':
				return str_pad(strtoupper(dechex($rom->read_varint($entry['size']))),$entry['size']*2, '0', STR_PAD_LEFT);
			case 'pointer':
				if ($format != 'html')
					return $this->read_pointer($entry['size'], false, isset($entry['endianness']) ? $entry['endianness'] : null,  isset($entry['base']) ? $entry['base'] : null);
				else
					return $this->read_pointer($entry['size'], true, isset($entry['endianness']) ? $entry['endianness'] : null, isset($entry['base']) ? $entry['base'] : null);
			break;
			case 'palette':
				if ($format != 'html')
					return $rom->read_palette($entry['size']);
				else
					return asprintf('<span class="palette" style="background-color: #%06X;">%1$06X</span>', $rom->read_palette($entry['size']));
			break;
			case 'binary':
				return decbin($rom->read_varint($entry['size']));
			break;
			case 'boolean':
				return $rom->read_varint($entry['size']) ? true : false;
			break;
			case 'tile':
				return $rom->read_tile($entry['bpp'], isset($entry['palette']) ? $entry['palette'] : -1, !isset(Main::get()->opts['yaml']));
			break;
			case 'asciitext':
				return $rom->read_string($bytesread, 'ascii', isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'UTF-16':
				return $rom->read_string($bytesread, 'utf16', isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'table':
				$bytesread = 0;
				return $this->read_table($offset, $offset+(isset($entry['size']) ? $entry['size'] : 0), $entry['entries'], false, isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
			case 'bytearray':
				return $rom->read_bytes($entry['size']);
			break;
			default:
				if (isset($game['texttables'][$entry['type']]))
					return trim($rom->read_string($bytesread, $game['texttables'][$entry['type']], isset($entry['terminator']) ? $entry['terminator'] : null, isset($opts['hideccs'])));
			break;
		}
		return $value;
	}
	private function read_table(&$offset, $end, $entries, $offsetkeys = true, $terminator = null) {
		global $rom, $platform;
		if ($terminator != null)
			$end = $offset + 0x10000;
		$output = array();
		$offsets = array();
		if ($rom->currentoffset() != $platform->map_rom($offset))
			throw new Exception(sprintf('Offset mismatch! %X != %X', $rom->currentoffset(), platform::get()->map_rom($offset)));
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
					debugvar($tmparray[$entry['name']], 'val');
					debugvar($terminator, 'terminator');
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
		global $rom, $platform;
		$offset = $rom->read_varint($size, -1, $endianness) + (($base != null) ? $base : 0);
		if (isset($this->pointerblocks[$offset]))
			return $this->pointerblocks[$offset];
		if (!$platform->isROM($offset))
			return $this->pointerblocks[$offset] = sprintf(core::addressformat, $offset);
		$datablock = getDataBlock($offset);
		if ($datablock == -1)
			return $this->pointerblocks[$offset] = sprintf(core::addressformat, $offset);
		if (!$html) {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('%s+%d ('.core::addressformat.')', decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = decimal_to_function($datablock);
		} else {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('<a href="%s#%3$X">%1$s+%2$d (%3$X)</a>', decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = sprintf('<a href="%s">%1$s</a>', decimal_to_function($datablock));
		}
	}
}
?>