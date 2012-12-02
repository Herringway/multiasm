<?php
function loadModules($path, $mod) {
	for ($dir = opendir($path); $file = readdir($dir); ) {
		if (substr($file, -4) == ".php") {
			require_once $path . $file;
			$modClass = substr($file,0, -4);
			$coremagicvalues[$modClass::magic] = $modClass;
		}
	}
	$mainmod = 'game';
	if (isset($coremagicvalues[$mod]))
		$mainmod = $coremagicvalues[$mod];
	return $mainmod;
}
if (count($_GET) > 0) {
	$options = '';
	if (isset($_GET['coremod']))
		$options[] = $_GET['coremod'];
	if (isset($_GET['param']))
		$options[] = $_GET['param'];
	foreach ($_GET as $key => $val)
		if (($key != 'param') && ($key != 'coremod')) {
			if ($val == 'true')
				$options[] = $key;
			else if ($val != null)
				$options[] = sprintf('%s=%s', urlencode($key), urlencode($val));
		}
	header(sprintf('Location: http://%s/%s/',$_SERVER['HTTP_HOST'], implode('/', $options)));
	die();
}
require_once 'libs/chromephp/ChromePhp.php';
require_once 'libs/commonfunctions.php';
require_once 'libs/cache.php';
require_once 'libs/settings.php';

ob_start();
$time_start = microtime(true);
$settings = new settings('settings.yml');

require_once 'web.php';

$cache = new cache();

$display = new display();
$argv = getArgv();
$format = $display->getFormat();

//Some debug output
debugvar($_SERVER, 'Server');

//Options!
$opts = $display->getOpts($argv);
$metadata['options'] = $opts;
debugvar($argv, 'args');
debugvar($opts, 'options');
debugmessage("Loading Core Modules", 'info');
//Load Modules
if ($settings['gamemenu']) {
	for ($dir = opendir('./games/'); $file = readdir($dir); ) {
		if (($file[0] != '.') && is_dir('./games/'.$file)) {
			$game = yaml_parse_file('./games/'.$file.'/'.$file.'.yml', 0);
			$metadata['gamelist'][$file] = gametitle($game);
		}
	}
	asort($metadata['gamelist']);
}
$mainmod = loadModules('./mods/', $argv[0]);
debugvar($mainmod, 'Main Module');
new $mainmod();

?>
