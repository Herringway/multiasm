<?php

class table_bytearray extends table_data {
	public function __getValue() {
		$output = array();
		for ($i = 0; $i < $this->details['Size']; $i++)
			$output[] = $this->source->getByte();
		return $output;
	}
}
class table_int extends table_data {
	public function __getValue() {
		$output = $this->source->getVar($this->details['Size']);
		if (isset($this->details['Signed']) && ($this->details['Signed']))
			$output = uint($this->details['Signed'], $this->details['Size']*8);
		if (isset($this->details['Values'][$output]))
			$output = $this->details['Values'][$output];
		if (isset($this->details['Bit Values'])) {
			$val = $output;
			$output = array();
			$i = 0;
			foreach ($this->details['Bit Values'] as $field) {
				if ($i > $this->details['Size']*8)
					break;
				$output[$field] = ($val&(1<<($i++))) > 0;
			}
		}
		return $output;
	}
}
class table_hexint extends table_data {
	private $intmod;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->intmod = new table_int($source, $gamedetails, $entry);
		$this->details = $entry;
	}
	public function __getValue() {
		$output = $this->intmod->getValue();
		return is_int($output) ? sprintf('%0'.($this->details['Size']*2).'X', $output) : $output;
	}
}
class table_binint extends table_data {
	private $intmod;
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->intmod = new table_int($source, $gamedetails, $entry);
		$this->details = $entry;
	}
	public function __getValue() {
		$output = $this->intmod->getValue();
		return is_int($output) ? sprintf('%0'.($this->details['Size']*8).'b', $output) : $output;
	}
}
class table_pointer extends table_data {
	public function __getValue() {
		$base = 0;
		if (isset($this->details['Base']))
			$base = $this->details['Base'];
		$offset = $this->source->getVar($this->details['Size']) + $base;
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
		$cpu = cpuFactory::getCPU($this->gamedetails['Processor']);
		return sprintf($cpu::addressFormat(), $offset);
	}
}
class table_struct extends table_data {
	public function __getValue() {
		//debugvar($this->details, 'table details');
		$output = array();
		$offsets = array();
		$initialoffset = $this->source->currentOffset();
		$i = 0;
		while (true) {
			$tmpoffset = $this->source->currentOffset();
			//debugvar($tmpoffset-$initialoffset, 'reloffset');
			if (isset($this->details['Size']) && ($this->details['Size'] <= $tmpoffset-$initialoffset))
				break;
			//debugvar($this->details['size'], 'size');
			$tmparray = array();
			$ints = array();
			foreach ($this->details['Entries'] as $entry) {
				if ($i++ > 0x10000)
					break 2;
				$size = isset($entry['Size']) ? $entry['Size'] : 0;
				$tmpoffsetkeys = $this->metadata['offsetkeys'];
				$this->metadata['offsetkeys'] = false;
				$value = $this->getSubValue(isset($entry['Type']) ? $entry['Type'] : 'int', $entry, $size);
				$this->metadata['offsetkeys'] = $tmpoffsetkeys;
				
				if (isset($entry['Name'])) {
					if (!isset($entry['Type']) || ($entry['Type'] == 'int'))
						$ints[$entry['Name']] = $value;
					$tmparray[$entry['Name']] = $value;
					
				} else {
					$tmparray[] = $value;
				}
				if (isset($this->details['Terminator']) && ($value == $this->details['Terminator'])) {
					//debugvar($tmparray[$entry['Name']], 'val');
					//debugvar($this->details['Terminator'], 'Terminator');
					break 2;
				}
			}
			if (isset($this->metadata['offsetkeys']) && $this->metadata['offsetkeys'])
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
		$valmod->setMetadata($this->metadata);
		if ($valmod instanceof table_data) { }
		else
			throw new Exception('Potential class name conflict');
		return $valmod->getValue();
	}
}
class table_bitfield extends table_data {
	public function __getValue() {
		$val = $this->source->getVar($this->details['Size']);
		$output = array();
		for ($i = 0; $i < count($this->details['Bit Values']); $i++)
			$output[$this->details['Bit Values'][$i]] = ($val&pow(2,$i)) != 0;
		return $output;
	}
}
class table_script extends table_data {
	public function __getValue() {
		$initialsize = (!isset($this->details['Size']) || $this->details['Size'] == 0) ? 0x100000 : $this->details['Size'];
		$terminator = null;
		$terminatorcount = 1;
		$terminatorsreached = 0;
		if (isset($this->details['Terminator']))
			$terminator = $this->details['Terminator'];
		if (isset($this->details['Terminator Repeat']))
			$terminatorcount = $this->details['Terminator Repeat'];
		$hideccs = false;
		if (isset($this->metadata['options']['NoCCs']) && $this->metadata['options']['NoCCs'])
			$hideccs = true;
		static $chars = 0;
		if (!isset($this->details['Charset']))
			$charset = $this->gamedetails['Default Script'];
		else
			$charset = $this->details['Charset'];
		$output = '';
		for ($i = 0; $i < $initialsize; $i++) {
			$length = 1;
			$val = $this->source->getByte();
			$vals = array($val);
			if ($charset === 'ascii') {
				$output .= chr($val);
			} else if ($charset === 'utf16') {
				$newval = $this->source->getByte()<<8;
				$val = $val + $newval;
				$vals[] = $newval;
				$output .= json_decode(sprintf('"\u%04X"',$val));
			} else {
				if (!isset($this->gamedetails['Script Tables'][$charset]))
					throw new Exception('Unknown Text Format');
				unset($replacement);
				if (isset($this->gamedetails['Script Tables'][$charset]['Replacements'][$val]))
					$replacement = $this->gamedetails['Script Tables'][$charset]['Replacements'][$val];
				if (isset($this->gamedetails['Script Tables'][$charset]['Lengths'][$val])) {
					$cval = 0;
					$length = $entry = $this->gamedetails['Script Tables'][$charset]['Lengths'][$val];
					if (is_array($entry))
						$length = $entry['default'];
						
					for ($j = 1; $j < $length; $j++) {
						$cval = $this->source->getByte();
						$val = ($val<<8) + $cval;
						$vals[] = $cval;
						if (isset($entry[$cval])) {
							$length = $entry = $entry[$cval];
							if (is_array($entry))
								$length = $entry['default'];
						}
						if (isset($replacement) && is_array($replacement) && isset($replacement[$cval]))
							$replacement = $replacement[$cval];
						//else
						//	unset($replacement);
						$i++;
					}
				}
				if (isset($replacement))
					$output .= $this->fillvalues($replacement, $val, $vals);
				else if (!$hideccs)
					$output .= sprintf('[%0'.(max($length,1)*2).'X]',$val);
			}
			if (($val === $terminator) && (++$terminatorsreached >= $terminatorcount))
				break;
		}
		return trim($output);
	}
	private function fillvalues($str, $fval, $ivals) {
		$needles = array('[VALUE]');
		$newneedles = array($fval);
		for ($i = 0; $i < count($ivals); $i++) {
			$needles[] = sprintf('[%02X]', $i);
			$newneedles[] = $ivals[$i];
		}
		return str_replace($needles, $newneedles, $str);
	}
}

class table_tile extends table_data {
	public function __getValue() {
		$data = $this->source->getString($this->details['Size']);
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