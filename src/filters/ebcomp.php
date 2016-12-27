<?php
/** 
	A lazy decompression data filter for earthbound's compression format
	
	A variant of the compression format used in Super Mario World.
	Has two additional commands: a bit-reversed buffer copy and a byte-reversed buffer copy.
*/
class ebcomp extends compression_filter {
	protected function decomp($offset) {
		$bpos = 0;
		debugmessage('decompressing...');
		while (($val = $this->dataSource->getByte()) !== 0xFF) {
			$cmdtype = $val >> 5;
			$len = ($val & 0x1F) + 1;
			if ($cmdtype === 7) {
				$nval = $this->dataSource->getByte();
				$cmdtype = ($val & 0x1C) >> 2;
				$len = (($val & 3) << 8) + $nval + 1;
			}
			if ($cmdtype >= 4) {
				$bpos = ($this->dataSource->getByte() << 8) + $this->dataSource->getByte();
			}
			switch ($cmdtype) {
			case 0: // uncompressed ?
				for ($i = 0; $i < $len; $i++)
					$this->buffer[] = $this->dataSource->getByte();
				break;
			case 1: // RLE ?
				$bval = $this->dataSource->getByte();
				for ($j = 0; $j < $len; ++$j)
					$this->buffer[] = $bval;
				break;
			case 2:
				$bval = $this->dataSource->getByte();
				$bval2 = $this->dataSource->getByte();
				while ($len-- !== 0) {
					$this->buffer[] = $bval;
					$this->buffer[] = $bval2;
				}
				break;
			case 3: // each byte is one more than previous ?
				$tmp = $this->dataSource->getByte();
				while ($len-- !== 0)
					$this->buffer[] = $tmp++;
				break;
			case 4: // use previous data ?
				for ($i = 0; $i < $len; $i++)
					$this->buffer[] = $this->buffer[$bpos++];
				break;
			case 5:
				while ($len-- !== 0) {
					$tmp = $this->buffer[$bpos++];
					$tmp = (($tmp >> 1) & 0x55) | (($tmp << 1) & 0xAA);
					$tmp = (($tmp >> 2) & 0x33) | (($tmp << 2) & 0xCC);
					$tmp = (($tmp >> 4) & 0x0F) | (($tmp << 4) & 0xF0);
					$this->buffer[] = $tmp;
				}
				break;
			case 6:
				while ($len-- !== 0) {
					$this->buffer[] = $this->buffer[$bpos--];
				}
				break;
			}
			if (count($this->buffer) >= $offset)
				break;
		}
	}
}
?>