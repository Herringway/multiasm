<?php
class platformFactory {
	static function _construct() { }
	static function getPlatform($name) {
		require_once 'platforms/'.$name.'.php';
		return new $name();
	}
}

?>