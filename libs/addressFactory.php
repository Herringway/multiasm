<?php
class addressFactory {
	private static $addrs = array();
	private static $currentID;
	public static function loadGame($id) {
		self::$currentID = $id;
		if (isset(self::$addrs[$id]))
			return self::$addrs[$id];
		debugmessage('Loading YAML for '.$id, 'info');
		if ($GLOBALS['settings']['cache']) {
			if (isset($this->cache[sprintf('MPASM.ymlmodified.%s', $id)]) && ($this->cache[sprintf('MPASM.ymlmodified.%s', $id)] === filemtime(sprintf('games/%1$s/%1$s.yml', $id)))) {
				debugmessage(sprintf("Game data (%s) loaded from cache", $id), 'info');
				list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)];
			} else { //Load game data & platform class from yml
				list($game,$addresses) = $this->cache[sprintf('MPASM.ymlcache.%s', $id)] = yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1);
				$this->cache[sprintf('MPASM.ymlmodified.%s', $id)] = filemtime(sprintf('games/%1$s/%1$s.yml', $id));
			}
			$output = array($game,$addresses);
		} else 
			$output = yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1);
		self::$addrs[$id] = $output;
		//return $output;
	}
	public static function getGameMetadata() {
		return self::$addrs[self::$currentID][0];
	}
	public static function getAddresses() {
		return self::$addrs[self::$currentID][1];
	}
	public static function getAddressFromName($name) {
		$v = self::getAddressEntryFromName($name)['Offset'];
		if ($v == null)
			return -1;
		return $v;
	}
	public static function getAddressFromOffset($offset) {
		$v = self::getAddressEntryFromOffset($offset)['Offset'];
		if ($v == null)
			return -1;
		return $v;
	}
	public static function getAddressEntryFromName($name) {
		foreach (self::$addrs[self::$currentID][1] as $addr) {
			if (isset($addr['Name']) && ($addr['Name'] == $name)) {
				return $addr;
			}
		}
		return null;
	}
	public static function getAddressEntryFromOffset($offset) {
		foreach (self::$addrs[self::$currentID][1] as $addr) {
			if (isset($addr['Offset']) && ($addr['Offset'] == $offset)) {
				return $addr;
			}
		}
		return null;
	}
}
?>