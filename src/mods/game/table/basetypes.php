<?php
interface __table_data {
	public function __construct(filter &$source, $gamedetails, $values);
	public function getValue();
}
abstract class table_data implements __table_data {
	protected $source;
	protected $entry;
	protected $offsets;
	protected $gamedetails;
	protected $metadata;
	private $result;
	protected static $math = null;
	private static $mathvars = array();
	protected function setVar($var, $val) {
		self::$mathvars[$var] = $val;
	}
	private function __setVars() {
		foreach (self::$mathvars as $var=>$val) {
			debugvar($var.'='.$val, 'setting');
			self::$math->evaluate(sprintf('%s = %d', strtolower(str_replace(array('?', ' ', '"', '/','+', '-','*'), array('Q', '_', '','D','A','S','M'), $var)), $val));
		}
		//$this->mathvars = array();
	}
	protected function evalString($str) {
		if (is_int($str))
			return $str;
		$this->__setVars();
		debugvar($str, 'evaluating');
		$result = self::$math->evaluate($str);
		debugvar($result, 'result');
		return $result;
	}
	public function __construct(filter &$source, $gamedetails, $entry) {
		if (self::$math === null) {
			debugmessage('generating new math');
			self::$math = new Webit\Util\EvalMath\EvalMath();
		}
		$this->source = $source;
		$this->details = $entry;
		if (isset($this->details['Size']))
			$this->details['Size'] = $this->evalString($this->details['Size']);
		$this->gamedetails = $gamedetails;
	}
	public function getOffsets() {
		return $this->offsets;
	}
	public function setMetadata(&$input) {
		$this->metadata = &$input;
	}
	public function getValue() {
		if (isset($this->result))
			return $this->result;
		$val = $this->__getValue();
		if (is_int($val) && isset($this->details['Name']))
			$this->setVar($this->details['Name'], $val);
		$this->result = $val;
		return $val;
	}
}
class table_unknown extends table_data {
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
			$output = uint($output, $this->details['Size']*8);
		if (isset($this->details['Base'])) {
			if (intval($this->details['Base']) < 2)
				throw new Exception('Impossible number base!');
			$output = strtoupper(base_convert($output, 10, $this->details['Base']));
		}
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
class table_pointer extends table_data {
	public function __getValue() {
		$base = 0;
		if (isset($this->details['Base']))
			$base = $this->details['Base'];
		$offset = $this->source->getVar($this->details['Size']) + $base;
		$target = addressFactory::getAddressSubentryFromOffset($offset, $this->source, $this->gamedetails);
		if ((!isset($this->metadata['nonamereplacement']) || !$this->metadata['nonamereplacement']) && ($target !== null)) {
			if (isset($target['Subname']))
				$name = sprintf('%s[%s]', $target['Name'], $target['Subname']);
			else if (isset($target['Name']))
				$name = $target['Name'];
		} else {
			$cpu = cpuFactory::getCPU($this->gamedetails['Platform']);
			$name = sprintf($cpu::addressFormat(), $offset);
		}
		return $name;
	}
}
class table_struct extends table_data {
	public function __getValue() {
		if (isset($this->details['Size'])) {
			$v = new table_array($this->source, $this->gamedetails, ['Size' => $this->details['Size'], 'Type' => 'array', 'Item Type' => ['Type' => 'struct', 'Entries' => $this->details['Entries']]]);
			$v->setMetadata($this->metadata);
			return $v->getValue();
		}
		$output = array();
		$offsets = array();
		$initialoffset = $this->source->currentOffset();
		$tmpoffset = $this->source->currentOffset();
		$tmparray = array();
		foreach ($this->details['Entries'] as $name=>$entry) {
			$coffs = $this->source->currentOffset();
			$size = isset($entry['Size']) ? $entry['Size'] : 0;
			$type = isset($entry['Type']) ? $entry['Type'] : 'int';
			if (isset($entry['Name']))
				$name = $entry['Name'];
			else
				$entry['Name'] = $name;
			if (file_exists('src/mods/game/table/'.$type.'.php') && !class_exists('table_'.$type))
				require_once 'src/mods/game/table/'.$type.'.php';
			if (!class_exists('table_'.$type))
				throw new Exception($type.' is unimplemented!');
			$typeclass = 'table_'.$type;
			$valmod = new $typeclass($this->source, $this->gamedetails, $entry);
			$valmod->setMetadata($this->metadata);
			if (!($valmod instanceof table_data))
				throw new Exception('Potential class name conflict');
			$tentry = $entry;
			$tentry['Count'] = 0;
			$this->offsets[$this->source->currentOffset()-$initialoffset] = $tentry;
			$value = $valmod->getValue();
			
			if (isset($this->details['Options']['Skip Blank Entries']) && $this->details['Options']['Skip Blank Entries'] && $valmod->isEmpty())
				continue;
			if (isset($entry['Pretty Name']))
				$name = $entry['Pretty Name'];
			else if (isset($entry['Description']) && isset($this->details['Options']['Use Descriptions as Struct Names']) && $this->details['Options']['Use Descriptions as Struct Names'])
				$name = $entry['Description'];
			if (isset($this->details['Options']['Prepend Offset']) && $this->details['Options']['Prepend Offset'])
				$name = sprintf('[%02X] ', $coffs-$tmpoffset).$name;
			$tmparray[$name] = $value;
			if (isset($this->details['Terminator']) && ($value == $this->details['Terminator']))
				break;
		}
//		if (isset($this->details['Options']['Skip Blank Entries']) && $this->details['Options']['Skip Blank Entries'] && ($tmparray === array()))
//			continue;
		if (isset($this->details['Options']['Use Offset as Key']) && !$this->details['Options']['Use Offset as Key'])
			$output[] = $tmparray;
		else
			$output[$tmpoffset] = $tmparray;
		return $output;
	}
	private function getSubValue($type, $entry, $size) {
		if (file_exists('src/mods/game/table/'.$type.'.php') && !class_exists('table_'.$type))
			require_once 'src/mods/game/table/'.$type.'.php';
		if (!class_exists('table_'.$type))
			throw new Exception($type.' is unimplemented!');
		$type = 'table_'.$type;
		$valmod = new $type($this->source, $this->gamedetails, $entry);
		$valmod->setMetadata($this->metadata);
		if (!($valmod instanceof table_data))
			throw new Exception('Potential class name conflict');
		return $valmod->getValue();
	}
}
class table_bitstruct extends table_data {
	public function __getValue() {
		$output = array();
		$offsets = array();
		$initialoffset = $this->source->currentOffset();
		$bitoffset = 0;
		$i = 0;
		while (true) {
			$tmpoffset = $this->source->currentOffset();
			if (isset($this->details['Size']) && ($this->details['Size'] <= $tmpoffset-$initialoffset))
				break;
			$tmparray = array();
			foreach ($this->details['Entries'] as $name=>$entry) {
				if ($i++ > 0x10000)
					break 2;
				$size = isset($entry['Size']) ? $entry['Size'] : 0;
				$tmpoffsetkeys = $this->metadata['offsetkeys'];
				$this->metadata['offsetkeys'] = false;
				$value = $this->getSubValue(isset($entry['Type']) ? $entry['Type'] : 'int', $entry, $size, $bitoffset);
				$bitoffset += $entry['Size'];
				$this->metadata['offsetkeys'] = $tmpoffsetkeys;
				if (isset($entry['Description']) && isset($this->metadata['Use Descriptions as Struct Names']) && $this->metadata['Use Descriptions as Struct Names'])
					$tmparray[$entry['Description']] = $value;
				else if (isset($entry['Name']))
					$tmparray[$entry['Name']] = $value;
				else
					$tmparray[] = $value;
				if (isset($this->details['Terminator']) && ($value == $this->details['Terminator']))
					break 2;
			}
			if (isset($this->metadata['offsetkeys']) && $this->metadata['offsetkeys'])
				$output[$tmpoffset] = $tmparray;
			else
				$output[] = $tmparray;
		}
		return $output;
	}
	private function getSubValue($type, $entry, $size, $bitoffset) {
		if (file_exists('src/mods/game/table/'.$type.'.php') && !class_exists('table_'.$type))
			require_once 'src/mods/game/table/'.$type.'.php';
		if (!class_exists('table_'.$type))
			throw new Exception($type.' is unimplemented!');
		$type = 'table_'.$type;
		$this->source->getByte();
		$memsrc = new memoryData();
		$memsrc->setData(array(0));
		$valmod = new $type($memsrc, $this->gamedetails, $entry);
		$valmod->setMetadata($this->metadata);
		if (!($valmod instanceof table_data))
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
class table_array extends table_data {
	public function __getValue() {
		$output = array();
		$offsets = array();
		$initialoffset = $this->source->currentOffset();
		$i = 0;
		$count = 0;
		while (true) {
			$tmpoffset = $this->source->currentOffset();
			if (isset($this->details['Size']) && ($this->details['Size'] <= $tmpoffset-$initialoffset))
				break;
			if ($i++ > 0x10000)
				break;
			$type = isset($this->details['Item Type']['Type']) ? $this->details['Item Type']['Type'] : 'int';
			
			if (file_exists('src/mods/game/table/'.$type.'.php') && !class_exists('table_'.$type))
				require_once 'src/mods/game/table/'.$type.'.php';
			if (!class_exists('table_'.$type))
				throw new Exception($type.' is unimplemented!');
			$typeclass = 'table_'.$type;
			$valmod = new $typeclass($this->source, $this->gamedetails, $this->details['Item Type']);
			$valmod->setMetadata($this->metadata);
			if (!($valmod instanceof table_data))
				throw new Exception('Potential class name conflict');
			$tentry = $this->details['Item Type'];
			$tentry['Count'] = $count;
			$this->offsets[$tmpoffset-$initialoffset] = $tentry;
			$value = $valmod->getValue();
			if (isset($this->details['Terminator']) && ($value == $this->details['Terminator']))
				break;
			$output[$tmpoffset] = $value;
			$count++;
		}
		return $output;
	}
}
class table_bytearray extends table_data {
	public function __getValue() {
		$this->details['Item Type'] = ['Type' => 'int', 'Size' => 1];
		$v = new table_array($this->source, $this->gamedetails, $this->details);
		$v->setMetadata($this->metadata);
		return $v->getValue();
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

class table_color extends table_data {
	public function __getValue() {
		$color = $this->source->getShort();
		if (isset($this->metadata['palette string']) && $this->metadata['palette string'])
			return sprintf('<div style="display: inline; background-color: #%06X;">&nbsp;</div>',(($color&31)<<19)+(($color&0x3E0)<<6)+(($color&0x7C00)>>7));
		else
			return (($color&31)<<19)+(($color&0x3E0)<<6)+(($color&0x7C00)>>7);
	}
}
class table_palette extends table_data {
	public function __getValue() {
		/*$data = $this->source->getString($this->details['Size']);
		$snespal = unpack('v*', $data);
		if (isset($this->metadata['palette string']) && $this->metadata['palette string']) {
			$palettes = '';
			for ($i = 1; $i <= $this->details['Size']/2; $i++)
				$palettes .= sprintf('<div style="display: inline; background-color: #%06X;">&nbsp;</div>',(($snespal[$i]&31)<<19)+(($snespal[$i]&0x3E0)<<6)+(($snespal[$i]&0x7C00)>>7));
		} else {
			$palettes = array();
			for ($i = 1; $i <= $this->details['Size']/2; $i++)
				$palettes[] = (($snespal[$i]&31)<<19)+(($snespal[$i]&0x3E0)<<6)+(($snespal[$i]&0x7C00)>>7);
		}
		return $palettes;*/
		$this->details['Item Type'] = ['Type' => 'color', 'Size' => 2];
		$v = new table_array($this->source, $this->gamedetails, $this->details);
		$v->setMetadata($this->metadata);
		return $v->getValue();
	}
}
?>
