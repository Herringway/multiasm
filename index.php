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

if ($settings['debug']) {
	ini_set('xdebug.var_display_max_depth', -1);
}
$cache = new cache();

require_once 'Twig/Autoloader.php';
Twig_Autoloader::register();
require_once 'libs/twigext.php';
header('Content-Type: text/html; charset=utf-8');
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array('debug' => $settings['debug']));
$twig->addExtension(new Twig_Extension_Debug());
$twig->addExtension(new Penguin_Twig_Extensions());

$uristring = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
$argv = array_slice(explode('/', $uristring),1);
if (strstr($argv[count($argv)-1], '.') !== FALSE)
	$argv[count($argv)-1] = strstr($argv[count($argv)-1], '.', true);
$opts = array();
for ($i = 2; $i < count($argv); $i++) {
	$v = explode('=', $argv[$i]);
	if (isset($v[1]))
		$opts[$v[0]] = $v[1];
	else
		$opts[$v[0]] = true;
}

$types = array();
if (function_exists('yaml_emit')) {
	$types['yml'] = 'yml';
	$types['yaml'] = 'yml';
}
if (function_exists('json_encode'))
	$types['json'] = 'json';
$v = substr(strrchr($_SERVER['REQUEST_URI'], '.'),1);
if (!isset($types[$v]))
	$format = 'html';
else
	$format = $types[$v];

//Some debug output
debugvar($_SERVER, 'Server');

//Options!
$metadata['options'] = $opts;
debugvar($argv, 'args');
debugvar($opts, 'options');
debugmessage("Loading Core Modules", 'info');
//Load Modules
if ($settings['gamemenu']) {
	for ($dir = opendir('./games/'); $file = readdir($dir); ) {
		if (($file[0] != '.') && is_dir('./games/'.$file)) {
			$gamedetails = yaml_parse_file('./games/'.$file.'/'.$file.'.yml', 0);
			$metadata['gamelist'][$file] = gametitle($gamedetails);
		}
	}
	asort($metadata['gamelist']);
}
$mainmod = loadModules('./mods/', $argv[0]);
debugvar($mainmod, 'Main Module');
$mod = new $mainmod();
$data = $mod->execute($argv);


//Display stuff
$displaydata = array_merge($metadata, $mod->getMetadata());
switch($format) {
case 'yml':
		header('Content-Type: text/plain; charset=UTF-8');
	if ($data !== null)
		foreach ($data as $yamldoc)
			if (!isset($yamldoc['hideme']) || !$yamldoc['hideme'])
				echo yaml_emit($yamldoc, YAML_UTF8_ENCODING, YAML_ANY_BREAK);
	break;
case 'json':
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
	break;
default:
	debugvar($mod->getMetadata()['template'], 'displaymode');
	header('Content-Type: text/html; charset=UTF-8');
	$displaydata['data'] = $data;
	echo $twig->render($displaydata['template'].'.tpl', $displaydata);
	break;
}
debugvar(sprintf('%f MB', memory_get_usage()/1024/1024), 'Total Memory usage');
debugvar(sprintf('%f seconds', microtime(true) - $time_start), 'Total Execution time');
?>
