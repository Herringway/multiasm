<?php
if (isset($_GET['begin'])) {
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
require_once 'Dwoo/dwooAutoload.php';
require_once 'commonfunctions.php';
if (!file_exists('settings.yml'))
	file_put_contents('settings.yml', yaml_emit(array('gameid' => 'eb', 'rompath' => '.', 'debug' => false)));
$settings = yaml_parse_file('settings.yml');
if ($settings['debug']) {
	require_once 'chromephp.php';
}
debugvar($_SERVER, 'server');
$routinename = '';
$arguments = array();

$argc = explode('/', str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']));
array_shift($argc);
if (isset($argc[0]) && ($argc[0] != null) && file_exists('games/'.$settings['gameid'].'.yml'))
	$settings['gameid'] = $argc[0];

list($gameorig,$known_addresses_p) = yaml_parse_file('games/'.$settings['gameid'].'.yml', -1);
$game = $gameorig;
require_once sprintf('platforms/%s.php', $game['platform']);

if (!isset($game['rombase']))
	$game['rombase'] = 0xC00000;
	
$offset = -1;
$game['size'] = filesize($settings['rompath'].$settings['gameid'].'.'.platform::extension);
@$handle = fopen($settings['rompath'].$settings['gameid'].'.'.platform::extension, 'r');

for ($i = 2; $i < count($argc); $i++) {
	$v = explode('=', $argc[$i]);
	if (isset($v[1]))
		$game[$v[0]] = $v[1];
	else
		$game[$v[0]] = true;
}
$known_addresses = ($known_addresses_p == null ? array() : $known_addresses_p) + platform::getRegisters();
$platform = new platform($handle, $game);
if (isset($known_addresses[$offset]['cpu']))
	require_once 'cpus/'.$known_addresses[$offset]['cpu'].'.php';
else
	require_once 'cpus/'.$game['processor'].'.php';

$core = new core($handle,$game,$known_addresses, $platform);
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
	if (isset($argc[1]) && ($argc[1] != null)) {
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
	debugvar($game, 'options');
	if (!$handle)
		die ('File not found!');
	
	if (isset($known_addresses[$offset]['type']) && (($known_addresses[$offset]['type'] == 'data') || ($known_addresses[$offset]['type'] == 'nullspace'))) {
		require 'table.php';
		header('Content-Type: text/html; charset=UTF-8');
		showtable($offset);
	} else {
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
			
		$branches = array();
		if (isset($known_addresses_p[$core->initialoffset]['labels']))
			$branches = $known_addresses_p[$core->initialoffset]['labels'];
		if (isset($game['genstub'])) {
			$branches = null;
			if (isset($known_addresses_p[$core->initialoffset]['labels']))
				$branches = $known_addresses_p[$core->initialoffset]['labels'];
			if ($core->branches !== null) {
				ksort($core->branches);
				$unknownbranches = 0;
				foreach ($core->branches as $k=>$branch)
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
			$output = preg_replace_callback('/ ?(\d+):/', 'hexafixer', yaml_emit($gameorig).yaml_emit($known_addresses_p));
			file_put_contents('games/'.$settings['gameid'].'.yml', $output);
		}
		if (isset($game['yaml'])) {
			header('Content-Type: text/plain; charset=UTF-8');
			echo yaml_emit($instructionlist);
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			$dwoo = new Dwoo();
			$dwoo->output('templates/'.core::template, array('routinename' => $routinename, 'title' => $game['title'], 'nextoffset' => isset($known_addresses[$nextoffset]['name']) ? $known_addresses[$nextoffset]['name'] : strtoupper(dechex($nextoffset)), 'game' => $settings['gameid'], 'instructions' => $instructionlist, 'arguments' => $arguments,'thisoffset' => $offset, 'options' => $game, 'offsetname' => $offsetname, 'addrformat' => core::addressformat, 'branches' => $branches, 'opcodeformat' => core::opcodeformat));
		}
	}
	fclose($handle);
}
?>
