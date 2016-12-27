<?php
class rom {
	private $handle;
	
	function __construct($filename) {
		$this->handle = fopen($filename, 'r');
	}
	
	public function seekTo($offset) {
		if ($offset != $this->currentoffset())
			fseek($this->handle, $offset);
	}
	public function currentoffset() {
		return ftell($this->handle);
	}
	public function getShort($offset = -1) {
		return $this->read_varint(2, $offset);
	}
	public function getByte($offset = -1) {
		return $this->read_varint(1, $offset);
	}
	public function read_strange() {
		$output = 0;
		$output += ord(fgetc($this->handle))<<(2*8);
		$output += ord(fgetc($this->handle))<<(0*8);
		$output += ord(fgetc($this->handle))<<(1*8);
		return $output;
	}
	public function read_varint($size, $offset = -1, $endianness = null) {
		if ($offset > 0)
			$this->seekTo($offset);
		$output = 0;
		if ($endianness == 'l')
			for ($i = 0; $i < $size; $i++)
				$output += ord(fgetc($this->handle))<<(($size-$i-1)*8);
		else if ($endianness == 'm') {
			$output += ord(fgetc($this->handle))<<(2*8);
			$output += ord(fgetc($this->handle))<<(0*8);
			$output += ord(fgetc($this->handle))<<(1*8);
		}
		else
			for ($i = 0; $i < $size; $i++)
				$output += ord(fgetc($this->handle))<<($i*8);
		return $output;
	}
	public function read($size, $offset = -1) {
		if ($offset > 0)
			$this->seekTo($offset);
		return fread($this->handle, $size);
	}
	
	public function read_palette($size) {
		$palettes = array();
		$snespal = unpack('v*', fread($this->handle,$size));
		for ($i = 1; $i <= $size/2; $i++)
			$palettes[] = (($snespal[$i]&31)<<19)+(($snespal[$i]&0x3E0)<<6)+(($snespal[$i]&0x7C00)>>7);
		return $palettes;
	}
	public function read_tile($bpp, $palette = -1, $outputbase64 = true) {
		$data = fread($this->handle, 8*$bpp);
		$curpos = $this->currentoffset();
		if ($palette >= 0) {
			$this->seekTo(platform::get()->map_rom($palette));
			$colours = $this->read_palette(pow(2,$bpp+1));
			$this->seekTo($curpos);
		} else
			$colours = array(0, 0, 0x3933FF, 0xDCFFFF, 0x330086,  0xBF7300,  0x00CFFF, 0x330086,  0xEFEBB4, 0x930000, 0x51FF00, 0xFFAC00, 0xBC11A4, 0x63CF63, 0x598CF2, 0xB6009F, 0x83DC00, 0xB8DE3A);
		if ($outputbase64) {
			$img = imagecreate(8,8);
			for ($i = 0; $i < pow(2,$bpp); $i++)
				$colour[] = imagecolorallocate($img, $colours[$i]&0xFF, ($colours[$i]>>8)&0xFF, $colours[$i]>>16);
			ImageColorTransparent($img, $colour[0]);
			for ($x = 0; $x < 8; $x++) {
				for ($y = 0; $y < 8; $y++) {
					$tile[$x][$y] = 0;
					for ($bitplane = 0; $bitplane < $bpp; $bitplane++)
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
			for ($x = 0; $x < 8; $x++)
				for ($y = 0; $y < 8; $y++) {
					$tile = 0;
					for ($bitplane = 0; $bitplane < $bpp; $bitplane++)
						$tile += ((ord($data[$y*2+(floor($bitplane/2)*16+($bitplane&1))]) & (1 << 7-$x)) >> 7-$x) << $bitplane;
					$output[$x][$y] = $tile;
				}
		}
		return $output;
	}
	public function read_bytes($numbytes) {
		$output = unpack('C*', fread($this->handle, $numbytes));
		return $output;
	}
	function read_bit_field($arg, $values) {
		$val = $this->read_varint($arg);
		$output = array();
		for ($i = 0; $i < count($values); $i++)
			$output[$values[$i]] = ($val&pow(2,$i)) != 0;
		return $output;
	}
}
?>