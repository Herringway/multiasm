<?php
set_time_limit(3600);
class table_script extends table_data {
	private $output;
	public function isEmpty() {
		return (trim($this->output) == '');
	}
	public function __getValue() {
		$initialsize = (!isset($this->details['Size']) || $this->details['Size'] == 0) ? 0x100000 : $this->details['Size'];
		$terminator = null;
		$terminatorcount = 1;
		$termtest = array();
		$terminatorsreached = 0;
		if (isset($this->details['Terminator']))
			$terminator = $this->details['Terminator'];
		if (isset($terminator) && !is_array($terminator))
			$terminator = array($terminator);
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
		$this->output = '';
		for ($i = 0; $i < $initialsize; $i++) {
			$length = 1;
			if (isset($this->gamedetails['Script Tables'][$charset]['Lengths']['default']))
				$length = $this->gamedetails['Script Tables'][$charset]['Lengths']['default'];
			$val = $this->source->getVar($length);
			$ccstring = sprintf('%02X', $val);
			$this->setVar('ARG_00', $val);
			$vals = array($val);
			if (($charset === 'ascii') || ($charset === 'sjis')) {
				$this->output .= chr($val);
			} else if ($charset === 'utf16') {
				$newval = $this->source->getByte()<<8;
				$val = $val + $newval;
				$vals[] = $newval;
				$this->output .= json_decode(sprintf('"\u%04X"',$val));
			} else {
				if (!isset($this->gamedetails['Script Tables'][$charset]))
					throw new Exception('Unknown Text Format');
				unset($replacement);
				if (isset($this->gamedetails['Script Tables'][$charset]['Replacements'][$val]))
					$replacement = $this->gamedetails['Script Tables'][$charset]['Replacements'][$val];
				if (isset($this->gamedetails['Script Tables'][$charset]['Lengths'][$val])) {
					$cval = 0;
					$entry = $this->gamedetails['Script Tables'][$charset]['Lengths'][$val];
					$curentry = $entry;
					if (is_array($entry))
						$curentry = $entry['default'];
					$length = $this->evalString($curentry);
						
					for ($j = 1; $j < $length; $j++) {
						$cval = $this->source->getByte();
						$this->setVar(sprintf('ARG_%02X', $j), $cval);
						$ccstring .= sprintf('%02X', $cval);
						$vals[] = $cval;
						if (is_array($entry) && isset($entry[$cval])) {
							$entry = $entry[$cval];
							$curentry = $entry;
							if (is_array($entry))
								$curentry = $entry['default'];
						}
						$length = $this->evalString($curentry);
						if (isset($replacement) && is_array($replacement) && isset($replacement[$cval]))
							$replacement = $replacement[$cval];
						if (isset($replacement) && is_array($replacement) && !isset($replacement[$cval]) && isset($replacement['default']))
							$replacement = $replacement['default'];
						if (isset($replacement) && is_array($replacement) && !isset($replacement[$cval]) && !isset($replacement['default']))
							unset($replacement);
						//else
						//	unset($replacement);
						$i++;
					}
					for ($j = 0; $j < $length; $j++)
						$this->setVar(sprintf('ARG_%02X', $j), 1);
				}
				if (isset($replacement))
					$this->output .= $this->fillvalues($replacement, $val, $vals);
				else if (!$hideccs)
					$this->output .= sprintf('[%s]',$ccstring);
			}
			if (isset($this->details['Terminator'])) {
				$termtest[] = $val;
				if (count($termtest) > count($terminator))
					array_shift($termtest);
				if (($termtest === $terminator) && (++$terminatorsreached >= $terminatorcount))
					break;
			}
			$i += $length-1;
		}
		if ($charset === 'sjis')
			$this->output = mb_convert_encoding($this->output, 'UTF-8', 'SJIS');
		return trim($this->output);
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
?>