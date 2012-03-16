<?php
register_shutdown_function('flagrant_system_error');
date_default_timezone_set('America/Halifax');
set_exception_handler('print_exception');
set_error_handler('error_handling');
define('BRANCH_LIMIT', 5000);
function print_exception($exception) {
	display::display_error(array('trace' => $exception->getTrace(), 'message' => $exception->getMessage()));
}
function error_handling($errno, $message, $file, $line) {
	static $errors = 0;
	//ini_set('display_errors', 'Off');
	if ($errors++ < 100)
		display::debugmessage(sprintf("%s on %s:%d", $message, $file, $line));
	return true;
}
function flagrant_system_error() {
	if (($error = error_get_last()) !== null) {
		if ($error['type'] == 1) {
			if (PHP_SAPI == 'cli')
				printf('SERIOUS ERROR: %s in %s:%d', $error['message'], $error['file'], $error['line']);
			else
				display::display_error(array('trace' => debug_backtrace(), 'message' => $error['message']));
		}
	} else {
		ob_end_flush();
	}
}
function hexafixer($matches) {
	if ($matches[0][0] === ' ')
		return sprintf(' 0x%04X:', $matches[1]);
	return sprintf('0x%06X:', $matches[1]);
}
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
	$output = unpack('C*', fread($handle, $numbytes));
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
	$snespal = unpack('v*', fread($handle,$size));
	for ($i = 1; $i <= $size/2; $i++)
		$palettes[] = (($snespal[$i]&31)<<19)+(($snespal[$i]&0x3E0)<<6)+(($snespal[$i]&0x7C00)>>7);
	return $palettes;
}
function asprintf($string, $haystack) {
	$output = array();
	foreach ($haystack as $needle)
		$output[] = vsprintf($string, $needle);
	return $output;
}
function read_string($handle, &$size, $table, $terminator = null) {
	$initialsize = ($size == 0) ? 0x100000 : $size;
	static $chars = 0;
	$output = '';
	for ($i = 0; $i < $initialsize; $i++) {
		if ($terminator !== null)
			$size++;
		$val = ord(fgetc($handle));
		if ($table === 'ascii') {
			$output .= chr($val);
		} else if ($table === 'utf16') {
			$val = $val + (ord(fgetc($handle))<<8);
			$output .= json_decode(sprintf('"\u%04X"',$val));
		} else {
			unset($replacement);
			if (isset($table['replacements'][$val]))
				$replacement = $table['replacements'][$val];
			if (isset($table['lengths'][$val])) {
				$cval = 0;
				$length = $entry = $table['lengths'][$val];
				if (is_array($entry))
					$length = $entry['default'];
					
				for ($j = 1; $j < $length; $j++) {
					$cval = ord(fgetc($handle));
					$val = ($val<<($j*8)) + $cval;
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
					if ($terminator !== null)
						$size++;
				}
			}
			$output .= !isset($replacement) ? sprintf('[%02X]',$val) : $replacement;
		}
		if ($val === $terminator) {
			break;
		}
	}
	return $output;
}
function read_tile($handle, $bpp, $palette = 0, $outputbase64 = true) {
	$data = fread($handle, 8*$bpp);
	$curpos = ftell($handle);
	if ($palette != 0) {
		fseek($palette);
		$colours = getpalette($handle, pow(2,$bpp));
		fseek($curpos);
	} else
		$colours = array(array(0, 0, 0),   array(0, 0, 0),    array(57, 51, 255), array(220, 255, 255), array(51, 0, 134),  array(191, 115, 0),  array(0, 207, 255), array(51, 0, 134),  array(239, 235, 180), 
								array(147, 0, 0), array(81, 255, 0), array(255, 172, 0), array(188, 17, 164),  array(99, 207, 99), array(89, 140, 242), array(182, 0, 159), array(131, 220, 0), array(184, 222, 58));
	if ($outputbase64) {
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
function load_settings() {
	if (!file_exists('settings.yml'))
		file_put_contents('settings.yml', yaml_emit(array('gameid' => 'eb', 'rompath' => '.', 'debug' => false, 'password' => 'changeme')));
	return yaml_parse_file('settings.yml');
}
abstract class platform_base {
	protected $main;
	public function getRegisters() {
		return array();
	}
	public function base() {
	}
	public function getMiscInfo() {
		return array();
	}
}

abstract class core_base {
	public $initialoffset;
	public $currentoffset;
	public $branches;
	public $placeholdernames = false;
	protected $main;
	const addressformat = '%X';
	const template = 'assembly';
	const opcodeformat = '%02X';
	function __construct(&$main) {
		$this->main = $main;
	}
	public static function getRegisters() {
		return array();
	}
	public function getDefault() {
	}
	public function getMisc() {
		return array();
	}
}
?>
