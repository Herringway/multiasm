<?php
class platformFactory {
	static private $platforms = array();
	static function _construct() { }
	static function getPlatform($name, $id = 'default') {
		assert($name != "", "No name provided");
		require_once 'src/platforms/'.$name.'.php';
		if (!isset(self::$platforms[$name.'/'.$id]))
			self::$platforms[$name.'/'.$id] = new $name();
		return self::$platforms[$name.'/'.$id];
	}
}

?>
