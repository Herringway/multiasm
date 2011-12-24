<?php
function convert_hex_keys_to_dec($tmpdata) {
	$arr = array();
	foreach ($tmpdata as $key => $data)
		$arr[hexdec($key)] = $data;
	return $arr;
}
function convert_hex_keys_to_hex($tmpdata) {
	$arr = array();
	foreach ($tmpdata as $key => $data)
		$arr['0x'.$key] = $data;
	return $arr;
}
function convert_dec_keys_to_hex($tmpdata) {
	$arr = array();
	foreach ($tmpdata as $key => $data)
		$arr[sprintf('%04X', $key)] = $data;
	return $arr;
}
function insert_yml($file, $data1, $data2) {
	if ($data2 != $data1)
		file_put_contents($file, Spyc::YAMLDump($data2));
}
function relative_to_absolute($offset, $val, $size) {
	return ($offset & 0xFF0000) + (($offset+uint($val+$size+1,$size*8))&0xFFFF);
}
function uint($i, $bits) {
	return $i < pow(2,$bits-1) ? $i : 0-(pow(2,$bits)-$i);
}
function get_bit_flags($arg, $values) {
	$output = array();
	for ($i = 0; $i < 8; $i++)
		if ($arg&pow(2,$i))
			$output[] = $values[$i];
	return implode(', ', $output);
}
function get_bit_flags2($arg, $values) {
	$output = array();
	for ($i = 0; $i < count($values); $i++)
		if ($arg&pow(2,$i))
			$output[] = $values[$i];
	return $output;
}

function read_bytes($handle, $numbytes) {
	$output = array();
	while ($numbytes--)
		$output[] = sprintf('%02X', ord(fgetc($handle)));
	return $output;
}
function read_int($handle, $size) {
	$output = 0;
	for ($i = 0; $i < $size; $i++)
		$output += ord(fgetc($handle))<<($i*8);
	return $output;
}
function read_palette($handle, $size) {
	$palettes = array();
	while ($size > 0) {
		$snespal = (ord(fgetc($handle)))+(ord(fgetc($handle))<<8);
		if (isset($_GET['YAML']))
			$palettes[] = sprintf('%u',((($snespal%32)*8)<<16)+(((($snespal>>5)%32)*8)<<8)+((($snespal>>10)%32)*8));
		else
			$palettes[] = sprintf('<div class="palette" style="background-color: #%06X;">%04X</div>',((($snespal%32)*8)<<16)+(((($snespal>>5)%32)*8)<<8)+((($snespal>>10)%32)*8), $snespal);
		$size -= 2;
	}
	return $palettes;
}
function getpalette($handle, $size) {
	$palettes = array();
	while ($size > 0) {
		$snespal = (ord(fgetc($handle)))+(ord(fgetc($handle))<<8);
		$palettes[] = array(((($snespal%32)*8)),(((($snespal>>5)%32)*8)),((($snespal>>10)%32)*8));
		$size -= 2;
	}
	return $palettes;
}
function read_string($handle, &$size, $table, $terminator = null) {
	$initialsize = ($size == 0) ? 0x100000 : $size;
	$output = '';
	for ($i = 0; $i < $initialsize; $i++) {
		if ($terminator !== null)
			$size++;
		$val = sprintf('%02X', ord(fgetc($handle)));
		if (isset($table['lengths'][hexdec($val)])) {
			$length = $table['lengths'][hexdec($val)];
			for ($j = 1; $j < $length; $j++) {
				$val .= sprintf('%02X', ord(fgetc($handle)));
				if (isset($table['lengths'][hexdec($val)]))
					$length = $table['lengths'][hexdec($val)];
				$i++;
				if ($terminator !== null)
					$size++;
			}
		}
		$output .= !isset($table['replacements'][hexdec($val)]) ? sprintf('[%s]',$val) : $table['replacements'][hexdec($val)];
		if (hexdec($val) === $terminator) {
			break;
		}
	}
	return $output;
}
function read_tile($handle, $bpp, $palette = 0) {
	$data = fread($handle, 8*$bpp);
	$curpos = ftell($handle);
	if ($palette != 0) {
		fseek($palette);
		$colours = getpalette($handle, pow(2,$bpp));
		fseek($curpos);
	} else
		$colours = array(array(0, 0, 0),   array(0, 0, 0),    array(57, 51, 255), array(220, 255, 255), array(51, 0, 134),  array(191, 115, 0),  array(0, 207, 255), array(51, 0, 134),  array(239, 235, 180), 
								array(147, 0, 0), array(81, 255, 0), array(255, 172, 0), array(188, 17, 164),  array(99, 207, 99), array(89, 140, 242), array(182, 0, 159), array(131, 220, 0), array(184, 222, 58));
	if (!isset($_GET['YAML'])) {
		$img = imagecreate(8,8);
		for ($i = 0; $i < pow(2,$bpp); $i++)
			$colour[] = imagecolorallocate($img, $colours[$i][0], $colours[$i][1], $colours[$i][2]);
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
		imagegif($img);
		$image = ob_get_contents();
		ob_end_clean();
		$output = sprintf('<img src="data:image/gif;base64,%s" />', base64_encode($image));
	} else { 
		for ($x = 0; $x < 8; $x++)
			for ($y = 0; $y < 8; $y++) {
				$tile = 0;
				for ($bitplane = 0; $bitplane < $bpp; $bitplane++)
					$tile += ((ord($data[$y*2+(floor($bitplane/2)*16+($bitplane&1))])    & (1 << 7-$x)) >> 7-$x) << $bitplane;
				$output[$x][$y] = $tile;
			}
	}
	return $output;
}
?>
