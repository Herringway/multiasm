<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'spyc.php';
require_once 'Dwoo/dwooAutoload.php';

$gameshort = 'eb';
$rompath = '../rms/';

$game = Spyc::YAMLLoad('games_defines/'.$gameshort.'asm/game.yml');

$known_addresses = convert_hex_keys_to_dec(Spyc::YAMLLoad('games_defines/'.$gameshort.'asm/known_offsets.yml'));

if (!isset($offset))
	$offset = 0xC00000;

$argc = (isset($_SERVER['PATH_INFO']) ? explode('/', $_SERVER['PATH_INFO']) : array('',''));
if (isset($argc[1]) && ($argc[1] != null) && is_dir('./games_defines/'.$argc[1].'asm'))
	$gameshort = $argc[1];
if (isset($argc[2]) && ($argc[2] != null))
	$offset = hexdec($argc[2]);
//((($snespal%32)*8)<<16)+(((($snespal>>5)%32)*8)<<8)+((($snespal>>10)%32)*8)

?>