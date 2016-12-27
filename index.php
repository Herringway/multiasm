<?php
require_once 'libs/commonfunctions.php';
require_once 'libs/cache.php';
require_once 'libs/settings.php';
require_once 'vendor/autoload.php';
require_once 'libs/twigext.php';
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
				$options[] = sprintf('%s=%s', $key, $val);
		}
	header(sprintf('Location: http://%s/%s/',$_SERVER['HTTP_HOST'], urlencode(implode('/', $options))));
	die();
}
$metadata['time_start'] = microtime(true);
$metadata['rootdir'] = '/';
if (isset($_SERVER['DOCUMENT_URI']))
	$metadata['rootdir'] = dirname($_SERVER['DOCUMENT_URI']);
$settings = new settings('settings.yml');

if ($settings['debug']) {
	ini_set('xdebug.var_display_max_depth', -1);
}
$cache = new cache();
if ($settings['cacheclear'])
	$cache->clear();

header('Content-Type: text/html; charset=utf-8');
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array('debug' => $settings['debug']));
$twig->addExtension(new Twig_Extension_Debug());
$twig->addExtension(new Penguin_Twig_Extensions());
if (!isset($argv)) {
	$console = false;
	if ($metadata['rootdir'] != '/')
		$uristring = str_replace($metadata['rootdir'], '', $_SERVER['REQUEST_URI']);
	$argv = array_slice(explode('/', $uristring),1);
} else {
	$console = true;
	array_shift($argv);
}
debugvar(substr($argv[count($argv)-1], 0, strrpos($argv[count($argv)-1], '.')), 'extension detection');
if ((substr($argv[count($argv)-1], 0, strrpos($argv[count($argv)-1], '.')) != $argv[count($argv)-1]) && (substr($argv[count($argv)-1], 0, strrpos($argv[count($argv)-1], '.')) != ''))
	$argv[count($argv)-1] = substr($argv[count($argv)-1], 0, strrpos($argv[count($argv)-1], '.'));
$metadata['options'] = array();
for ($i = 2; $i < count($argv); $i++) {
	$v = explode('=', $argv[$i]);
	if (isset($v[1]))
		$metadata['options'][$v[0]] = urldecode($v[1]);
	else
		$metadata['options'][$v[0]] = true;
}

$types = array();
if (function_exists('yaml_emit')) {
	$types['yml'] = 'yml';
	$types['yaml'] = 'yml';
}
if (function_exists('json_encode'))
	$types['json'] = 'json';

if ($console)
	$format = 'console';
else {
	$v = substr(strrchr($_SERVER['REQUEST_URI'], '.'),1);
	if (!isset($types[$v]))
		$format = 'html';
	else
		$format = $types[$v];
}
//Some debug output
debugvar($_SERVER, 'Server');

//Options!
debugvar($argv, 'args');
debugvar($metadata['options'], 'options');
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
$mod->setMetadata($metadata);
$data = $mod->getData($argv);

//Display stuff
$displaydata = array_merge($metadata, $mod->getMetadata());
switch($format) {
case 'yml':
		header('Content-Type: text/plain; charset=UTF-8');
	if ($data !== null)
		//foreach ($data as $yamldoc)
		//	if (!isset($yamldoc['hideme']) || !$yamldoc['hideme'])
				echo yaml_emit($data, YAML_UTF8_ENCODING, YAML_ANY_BREAK);
	break;
case 'json':
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
	break;
case 'console':
	debugvar($mod->getMetadata()['template'], 'displaymode');
	$displaydata['data'] = $data;
	echo $twig->render($displaydata['template'].'_console.tpl', $displaydata);
	break;
default:
	debugvar($mod->getMetadata()['template'], 'displaymode');
	header('Content-Type: text/html; charset=UTF-8');
	$displaydata['data'] = $data;
	if (isset($GLOBALS['ERRORS']))
		$displaydata['errors'] = $GLOBALS['ERRORS'];
	echo $twig->render($displaydata['template'].'.tpl', $displaydata);
	break;
}
?>
