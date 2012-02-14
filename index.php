<?php
if (array_key_exists('begin', $_GET)) {
	$options = '';
	foreach ($_GET as $key => $val)
		if (($key != 'begin') && ($key != 'game')) {
			if ($val == 'true')
				$options[] = $key;
			else
				$options[] = sprintf('%s=%s', $key, $val);
		}
	header(sprintf('Location: http://%s/%s/%s/%s',$_SERVER['SERVER_NAME'],$_GET['game'], $_GET['begin'], implode('/', $options)));
	die();
}
//echo '<pre>'; var_dump($_SERVER); echo '</pre>';
require_once '../hexview.php';
require_once 'Dwoo/dwooAutoload.php';
require_once 'commonfunctions.php';
if (!file_exists('settings.yml'))
	file_put_contents('settings.yml', yaml_emit(array('gameid' => 'eb', 'rompath' => '.')));
$settings = yaml_parse_file('settings.yml');

$options = '';
$routinename = '';
$arguments = array();
$gameid = $settings['gameid'];

$argc = explode('/', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']));
array_shift($argc);
if (array_key_exists(0, $argc) && ($argc[0] != null) && is_dir('./games/'.$argc[0]))
	$gameid = $argc[0];

if (!file_exists('games/'.$gameid.'/known_offsets.yml'))
	file_put_contents('games/'.$gameid.'/known_offsets.yml', yaml_emit(array()));
$game = yaml_parse_file('games/'.$gameid.'/game.yml');
require_once sprintf('platforms/%s.php', $game['platform']);

$known_addresses_p = yaml_parse_file('games/'.$gameid.'/known_offsets.yml');
$known_addresses = $known_addresses_p + yaml_parse_file('platforms/'.$game['platform'].'_registers.yml');
if (!isset($game['rombase']))
	$game['rombase'] = 0xC00000;
	
$offset = -1;
$game['size'] = filesize($settings['rompath'].$gameid.'.'.platform::extension);
@$handle = fopen($settings['rompath'].$gameid.'.'.platform::extension, 'r');
if (!isset($argc[1]))
	$argc[1] = null;
switch ($argc[1]) {
case 'stats':
	require_once 'gamestats.php';
	showstats();
	break;
case 'issues':
	require_once 'diagnostic.php';
	diagnose();
	break;
case 'rommap':
	require_once 'listdata.php';
	listdata();
	break;
default:
	$offsetname = '';
	if (array_key_exists(1, $argc) && ($argc[1] != null)) {
		$offsetname = $argc[1];
		$offset = hexdec($argc[1]);
		$argc[1] = $argc[1];
		if (strtoupper(dechex(hexdec($argc[1]))) != strtoupper($argc[1])) {
			foreach ($known_addresses as $k => $addr)
				if (isset($addr['name']) && ($addr['name'] == $argc[1])) {
					$offset = $k;
					$offsetname = $argc[1];
					break;
				}
		}
	}
	for ($i = 2; $i < count($argc); $i++) {
		$v = explode('=', $argc[$i]);
		if (isset($v[1]))
			$game[$v[0]] = $v[1];
		else
			$game[$v[0]] = true;
	}
	if (!$handle)
		die ('File not found!');
	
	if (isset($known_addresses[$offset]['type']) && (($known_addresses[$offset]['type'] == 'data') || ($known_addresses[$offset]['type'] == 'nullspace'))) {
		require 'table.php';
		header('Content-Type: text/html; charset=UTF-8');
		showtable($offset);
	} else {
		if (isset($known_addresses[$offset]['cpu']))
			require_once 'cpus/'.$known_addresses[$offset]['cpu'].'.php';
		else
			require_once 'cpus/'.$game['processor'].'.php';
		
		$core = new core($handle,$game,$known_addresses);
		if ($offset == -1)
			$offset = $core->getDefault();
		$instructionlist = $core->execute($offset,$offsetname);
		$nextoffset = $core->currentoffset;
		$offset = $core->initialoffset;

		if (isset($known_addresses[$offset]['description']))
			$routinename = $known_addresses[$offset]['description'];
		else
			$routinename = sprintf('%X', $offset);
		if (isset($known_addresses[$offset]['arguments']))
			$arguments = $known_addresses[$offset]['arguments'];
		if (isset($game['genstub'])) {
			$branches = null;
			if ($core->branches !== null) {
				ksort($core->branches);
				$unknownbranches = 0;
				foreach ($core->branches as $k=>$branch)
					if ($k < 0x10000)
						$branches[$k] = 'UNKNOWN'.$unknownbranches++;
			}
			if (!isset($known_addresses_p[$core->initialoffset]['name']))
				$known_addresses_p[$core->initialoffset]['name'] = '';
			if (!isset($known_addresses_p[$core->initialoffset]['description']))
				$known_addresses_p[$core->initialoffset]['description'] = '';
			$known_addresses_p[$core->initialoffset]['type'] = 'assembly';
			$known_addresses_p[$core->initialoffset]['size'] = $core->currentoffset-$core->initialoffset;
			foreach ($core->getMisc() as $k=>$val)
				$known_addresses_p[$core->initialoffset][$k] = $val;
			$known_addresses_p[$core->initialoffset]['labels'] = $branches;
			ksort($known_addresses_p);
			foreach ($known_addresses_p as $k=>$offset)
				$output[sprintf('0x%06X',$k)] = $offset;
			file_put_contents('games/'.$gameid.'/known_offsets.yml', preg_replace('/"0x([0-9a-fA-F]{6})":/', '0x\1:', yaml_emit($output)));
		}
		if (isset($game['yaml'])) {
			header('Content-Type: text/plain; charset=UTF-8');
			echo yaml_emit($instructionlist);
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			$dwoo = new Dwoo();
			$dwoo->output('templates/'.$game['platform'].'.tpl', array('routinename' => $routinename, 'title' => $game['title'], 'nextoffset' => isset($known_addresses[$nextoffset]['name']) ? $known_addresses[$nextoffset]['name'] : strtoupper(dechex($nextoffset)), 'game' => $gameid, 'instructions' => $instructionlist, 'arguments' => $arguments,'thisoffset' => $offset, 'options' => $game, 'offsetname' => $offsetname));
		}
	}
	fclose($handle);
}
?>
