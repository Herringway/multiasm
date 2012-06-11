<?php
class settings implements arrayaccess {
	private $settings;
	private $defaults = array(
		'gameid' => 'eb',
		'rompath' => '.',
		'debug' => false,
		'gamemenu' => false,
		'cache' => true,
		'admins' => array(),
		'errorlimit' => 40,
		'localvar format' => '.%s');
	public function __construct($filename) {
		if (!file_exists($filename))
			file_put_contents($filename, yaml_emit($this->defaults));
		$this->settings = yaml_parse_file($filename);
	}
	public function offsetSet($key, $value) {
		if (!isset($this->defaults[$key]))
			throw new Exception(sprintf('Unknown setting: %s!', $key));
		return $this->settings[$key] = $value;
    }
    public function offsetExists($key) {
		return isset($this->defaults[$key]);
    }
    public function offsetUnset($key) {
		unset($this->settings[$key]);
    }
    public function offsetGet($key) {
		if (!isset($this->defaults[$key]))
			throw new Exception(sprintf('Unknown setting: %s!', $key));
		return default_value($this->settings, $key, $this->defaults[$key]);
    }
}
function default_value($array, $key, $default = false) {
	if (!isset($array[$key]))
		return $default;
	return $array[$key];
}
?>