<?php
require_once 'libs/commonfunctions.php';
require_once 'libs/rom.php';
require_once 'libs/cache.php';
require_once 'libs/settings.php';


ob_start();
$time_start = microtime(true);
$settings = new settings('settings.yml');

if (PHP_SAPI === 'cli')
	require_once 'cli.php';
else
	require_once 'web.php';

$cache = new cache();

$display = new display();
$argv = $display->getArgv();
$format = $display->getFormat();

//Some debug output
debugvar($_SERVER, 'Server');
debugvar($argv, 'args');

//Options!
$opts = $display->getOpts($argv);
debugvar($opts, 'options');
$godpowers = $display->canWrite();
debugmessage("Loading Core Modules");
//Load Modules
for ($dir = opendir('./mods/'); $file = readdir($dir); ) {
	if (substr($file, -4) == ".php") {
		require_once './mods/' . $file;
		$modClass = substr($file,0, -4);
		$coremagicvalues[$modClass::magic] = $modClass;
	}
}
if ($settings['gamemenu'])
	for ($dir = opendir('./games/'); $file = readdir($dir); ) {
		if (($file[0] != '.') && is_dir('./games/'.$file)) {
			$game = yaml_parse_file('./games/'.$file.'/'.$file.'.yml', 0);
			$metadata['gamelist'][$file] = gametitle($game);
		}
	}
asort($gamelist);
$mainmod = 'game';
if (isset($coremagicvalues[$argv[0]]))
	$mainmod = $coremagicvalues[$argv[0]];
	
debugvar($mainmod, 'main module');
new $mainmod();

?>
