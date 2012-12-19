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
	public function __construct(filter $source, $gamedetails, $entry) {
		$this->source = $source;
		$this->details = $entry;
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
		return $offset;
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
		$initialsize = ($this->details['size'] == 0) ? 0x100000 : $this->details['size'];
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

?>