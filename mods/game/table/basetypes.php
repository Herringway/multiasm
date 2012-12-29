<?php

class table_bytearray implements table_data {
	private $source;
	private $size;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->size = $entry['size'];
	}
	public function getValue() {
		$output = array();
		for ($i = 0; $i < $this->size; $i++)
			$output[] = $this->source->getByte();
		return $output;
	}
}
class table_int implements table_data {
	private $source;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
	}
	public function getValue() {
		$output = $this->source->getVar($this->details['size']);
		if (isset($this->details['signed']) && ($this->details['signed']))
			$output = uint($this->details['signed'], $this->details['size']*8);
		if (isset($this->details['values'][$output]))
			$output = $this->details['values'][$output];
		return $output;
	}
}
class table_hexint implements table_data {
	private $intmod;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->intmod = new table_int($source, $gamedetails, $entry);
		$this->details = $entry;
	}
	public function getValue() {
		$output = $this->intmod->getValue();
		return is_int($output) ? sprintf('%0'.($this->details['size']*2).'X', $output) : $output;
	}
}
class table_binint implements table_data {
	private $intmod;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->intmod = new table_int($source, $gamedetails, $entry);
		$this->details = $entry;
	}
	public function getValue() {
		$output = $this->intmod->getValue();
		return is_int($output) ? sprintf('%0'.($this->details['size']*8).'b', $output) : $output;
	}
}
class table_pointer implements table_data {
	private $source;
	private $details;
	private $gamedetails;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
		$this->gamedetails = $gamedetails;
	}
	public function getValue() {
		$base = 0;
		if (isset($this->details['base']))
			$base = $this->details['base'];
		$offset = $this->source->getVar($this->details['size']) + $base;
		/*if (isset($this->pointerblocks[$offset]))
			return $this->pointerblocks[$offset];
		if ($this->platform->identifyArea($offset) != 'rom')
			return $this->pointerblocks[$offset] = $offset;
		$datablock = getDataBlock($offset);
		if ($datablock == -1)
			return $this->pointerblocks[$offset] = $offset;
		if (!$html) {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('%s+%d ('.core::addressformat.')', decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = decimal_to_function($datablock);
		} else {
			if ($datablock != $offset)
				return $this->pointerblocks[$offset] = sprintf('<a href="%s#%3$X">%1$s+%2$d (%3$X)</a>', decimal_to_function($datablock), $offset-$datablock, $offset);
			return $this->pointerblocks[$offset] = sprintf('<a href="%s">%1$s</a>', decimal_to_function($datablock));
		}*/
		$cpu = cpuFactory::getCPU($this->gamedetails['processor']);
		return sprintf($cpu::addressFormat(), $offset);
	}
}
class table_table implements table_data {
	private $source;
	private $details;
	private $offsetkeys;
	private $gamedetails;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
		$this->gamedetails = $gamedetails;
		$this->offsetkeys = false;
	}
	public function useOffsetKeys($option) {
		$this->offsetkeys = $option;
	}
	public function getValue() {
		debugvar($this->details, 'table details');
		$output = array();
		$offsets = array();
		$initialoffset = $this->source->currentOffset();
		$i = 0;
		while (true) {
			$tmpoffset = $this->source->currentOffset();
			//debugvar($tmpoffset-$initialoffset, 'reloffset');
			if (isset($this->details['size']) && ($this->details['size'] <= $tmpoffset-$initialoffset))
				break;
			//debugvar($this->details['size'], 'size');
			$tmparray = array();
			$ints = array();
			foreach ($this->details['entries'] as $entry) {
				if ($i++ > 0x10000)
					break 2;
				if (isset($entry['size']))
					$entry['size'] = eval('return '.str_replace(array_keys($ints), $ints, $entry['size']).';');
				$size = isset($entry['size']) ? $entry['size'] : 0;
				
				$value = $this->getSubValue(isset($entry['type']) ? $entry['type'] : 'int', $entry, $size);
				
				if (isset($entry['name'])) {
					if (!isset($entry['type']) || ($entry['type'] == 'int'))
						$ints[$entry['name']] = $value;
					$tmparray[$entry['name']] = $value;
					
				} else {
					$tmparray[] = $value;
				}
				if (isset($this->details['terminator']) && ($value == $this->details['terminator'])) {
					debugvar($tmparray[$entry['name']], 'val');
					debugvar($this->details['terminator'], 'terminator');
					break 2;
				}
			}
			if ($this->offsetkeys)
				$output[$tmpoffset] = $tmparray;
			else
				$output[] = $tmparray;
		}
		return $output;
	}
	private function getSubValue($type, $entry, $size) {
		if (file_exists($type.'.php'))
			require_once 'mods/game/table/'.$type.'.php';
		if (!class_exists('table_'.$type))
			throw new Exception($type.' is unimplemented!');
		$type = 'table_'.$type;
		$valmod = new $type($this->source, $this->gamedetails, $entry);
		if ($valmod instanceof table_data) { }
		else
			throw new Exception('Potential class name conflict');
		return $valmod->getValue();
	}
}
class table_bitfield implements table_data {
	private $source;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
	}
	public function getValue() {
		$val = $this->source->getVar($this->details['size']);
		$output = array();
		for ($i = 0; $i < count($this->details['bitvalues']); $i++)
			$output[$this->details['bitvalues'][$i]] = ($val&pow(2,$i)) != 0;
		return $output;
	}
}
class table_text implements table_data {
	private $source;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
		$this->gamedetails = $gamedetails;
	}
	public function getValue() {
		$initialsize = (!isset($this->details['size']) || $this->details['size'] == 0) ? 0x100000 : $this->details['size'];
		$hideccs = false;
		static $chars = 0;
		if (!isset($this->details['charset']))
			$charset = $this->gamedetails['defaulttext'];
		else
			$charset = $this->details['charset'];
		$output = '';
		for ($i = 0; $i < $initialsize; $i++) {
			$length = 1;
			$val = $this->source->getByte();
			if ($charset === 'ascii') {
				$output .= chr($val);
			} else if ($charset === 'utf16') {
				$val = $val + ($this->source->getByte()<<8);
				$output .= json_decode(sprintf('"\u%04X"',$val));
			} else {
				if (!isset($this->gamedetails['texttables'][$charset]))
					throw new Exception('Unknown Text Format');
				unset($replacement);
				if (isset($this->gamedetails['texttables'][$charset]['replacements'][$val]))
					$replacement = $this->gamedetails['texttables'][$charset]['replacements'][$val];
				if (isset($this->gamedetails['texttables'][$charset]['lengths'][$val])) {
					$cval = 0;
					$length = $entry = $this->gamedetails['texttables'][$charset]['lengths'][$val];
					if (is_array($entry))
						$length = $entry['default'];
						
					for ($j = 1; $j < $length; $j++) {
						$cval = $this->source->getByte();
						$val = ($val<<8) + $cval;
						if (isset($entry[$cval])) {
							$length = $entry = $entry[$cval];
							if (is_array($entry))
								$length = $entry['default'];
						}
						if (isset($replacement[$cval]))
							$replacement = $replacement[$cval];
						else
							unset($replacement);
						$i++;
					}
				}
				if (isset($replacement))
					$output .= $replacement;
				else if (!$hideccs)
					$output .= sprintf('[%0'.(max($length,1)*2).'X]',$val);
			}
			if (isset($this->details['terminator']) && ($val === $this->details['terminator']))
				break;
		}
		return trim($output);
	}
}

class table_tile implements table_data {
	private $source;
	private $details;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
		$this->gamedetails = $gamedetails;
	}
	public function getValue() {
		$data = $this->source->getString($this->details['size']);
		$output = array();
		//debugmessage('data:');
		$colours = array(0, 0, 0x3933FF, 0xDCFFFF, 0x330086,  0xBF7300,  0x00CFFF, 0x330086,  0xEFEBB4, 0x930000, 0x51FF00, 0xFFAC00, 0xBC11A4, 0x63CF63, 0x598CF2, 0xB6009F, 0x83DC00, 0xB8DE3A);
		/*if (false) {
			$img = imagecreate(8,8);
			for ($i = 0; $i < pow(2,$this->details['bpp']); $i++)
				$colour[] = imagecolorallocate($img, $colours[$i]&0xFF, ($colours[$i]>>8)&0xFF, $colours[$i]>>16);
			ImageColorTransparent($img, $colour[0]);
			for ($x = 0; $x < 8; $x++) {
				for ($y = 0; $y < 8; $y++) {
					$tile[$x][$y] = 0;
					for ($bitplane = 0; $bitplane < $this->details['bpp']; $bitplane++)
						$tile[$x][$y] += ((ord($data[$y*2+(floor($bitplane/2)*16+($bitplane&1))])    & (1 << 7-$x)) >> 7-$x) << $bitplane;
					if ($tile[$x][$y] != 0)
						imagesetpixel($img,$x,$y,$colour[$tile[$x][$y]]);
				}
			}
			ob_start();
			imagepng($img);
			$image = ob_get_contents();
			ob_end_clean();
			$output = sprintf('<img src="data:image/png;base64,%s"/>', base64_encode($image));
		} else { 
				}
		}*/
			/*for ($x = 0; $x < 8; $x++)
				for ($y = 0; $y < 8; $y++) {
					$tile = 0;
					for ($bitplane = 0; $bitplane < $this->details['bpp']; $bitplane++) { }
						//$tile += ((ord($data[$y*2+(floor($bitplane/2)*16+($bitplane&1))]) & (1 << 7-$x)) >> 7-$x) << $bitplane;
					$output[$x][$y] = $tile;
				}*/
		return $output;
	}
}
?>