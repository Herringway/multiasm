<?php
require_once 'src/platformFactory.php';
class addressFactory {
	private static $addrs = array();
	private static $currentID;
	private static $platforms = array();
	public static function loadGame($id) {
		$tags = ['!assembly' => 'assemblytag', '!data' => 'datatag', '!empty' => 'emptytag', '!struct' => 'structtag', '!int' => 'inttag', '!script' => 'scripttag', '!array' => 'arraytag', '!pointer' => 'pointertag', '!tile' => 'tiletag', '!unknown' => 'unknowntag', '!color' => 'colortag'];
		self::$currentID = $id;
		$ndocs = 0;
		if (isset(self::$addrs[$id]))
			return self::$addrs[$id];
		debugmessage('Loading YAML for '.$id, 'info');
		if ($GLOBALS['settings']['cache']) {
			global $cache;
			if (isset($cache[sprintf('MPASM.ymlmodified.%s', $id)]) && ($cache[sprintf('MPASM.ymlmodified.%s', $id)] === filemtime(sprintf('games/%1$s/%1$s.yml', $id)))) {
				debugmessage(sprintf("Game data (%s) loaded from cache", $id), 'info');
				list($game,$addresses) = $cache[sprintf('MPASM.ymlcache.%s', $id)];
			} else { //Load game data & platform class from yml
				list($game,$addresses) = $cache[sprintf('MPASM.ymlcache.%s', $id)] = yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1, $ndocs, $tags);
				$cache[sprintf('MPASM.ymlmodified.%s', $id)] = filemtime(sprintf('games/%1$s/%1$s.yml', $id));
			}
			$output = array($game,$addresses);
		} else 
			$output = yaml_parse_file(sprintf('games/%1$s/%1$s.yml', $id), -1, $ndocs, $tags);
		self::$platforms[$id] = platformFactory::getPlatform($output[0]['Platform']);
		self::$addrs[$id] = $output;
	}
	public static function loadPlatformAddresses($id) {
		self::$addrs[$id][1] += self::$platforms[$id]->getPlatformAddresses();
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
		foreach (self::$addrs[self::$currentID][1] as $key=>$addr)
			if ($key == $name) {
				$addr['Name'] = $key;
				return $addr;
			}
		return null;
	}
	public static function getAddressEntryFromOffset($offset) {
		try {
			$tmpo = self::$platforms[self::$currentID]->map($offset);
		} catch(Exception $e) {
			return null;
		}
		foreach (self::$addrs[self::$currentID][1] as $k=>$addr)
			if (isset($addr['Offset']) && (self::$platforms[self::$currentID]->map($addr['Offset']) == $tmpo)) {
				$addr['Name'] = $k;
				return $addr;
			}
		return null;
	}
	public static function getAddressSubnameFromOffset($offset) {
		foreach (self::$addrs[self::$currentID][1] as $k=>$addr)
			if (isset($addr['Offset']) && ($addr['Offset']-$offset <= $addr['Size'])) {
				$addr['Name'] = $k;
				return $k;
			}
		return null;
	}
	public static function getAddressSubentryFromOffset($offset, $source, $game) {
		if ($GLOBALS['settings']['Resolve Addresses']) {
				foreach (self::$addrs[self::$currentID][1] as $k=>$addr) {
				if (isset($addr['Offset']) && isset($addr['Size']) && ($offset-$addr['Offset'] <= $addr['Size']-1) && ($offset-$addr['Offset'] > 0)) {
					$addr['Index'] = $offset-$addr['Offset'];
					$addr['Name'] = $k;
					if (isset($addr['Labels'][$addr['Index']])) {
						$addr['Subname'] = $addr['Labels'][$addr['Index']];
						unset($addr['Index']);
					}
					else if (isset($addr['Entries']) && $GLOBALS['settings']['Struct Addresses']) {
						require_once 'src/mods/game/table/basetypes.php';
						$tablemod = new table_struct($source, $game, $addr);
						$source->seekTo($addr['Offset']);
						$tablemod->getValue();
						$o = $tablemod->getOffsets();
						$toffs = $offs = $offset-$addr['Offset'];
						$usecount = false;
						foreach ($o as $k=>$v) {
							if ($v['Count'] > 0) {
								$usecount = true;
								break;
							}
						}
						while (true) {
							if ($offs <= 0)
								break;
							if (isset($o[$offs])) {
								break;
							}
							$offs--;
						}
						if (isset($addr['Subname']))
							$addr['Subname'] = $o[$offs]['Name'];
						if (isset($o[$offs]['Values']))
							$addr['Values'] = $o[$offs]['Values'];
						if (isset($o[$offs]['Return Values']))
							$addr['Return Values'] = $o[$offs]['Return Values'];
						if (isset($o[$offs]['Notes']))
							$addr['Notes'] = $o[$offs]['Notes'];
						//var_dump($o); die;
						$addr['Index'] = $toffs-$offs;
						if ($usecount)
							$addr['Count'] = $o[$offs]['Count'];
						if (($toffs-$offs) == 0)
							unset($addr['Index']);
					}
					return $addr;
				} else if (isset($addr['Offset']) && ($addr['Offset'] == $offset)) {
					$addr['Name'] = $k;
					return $addr;
				}
			}
		}
		return null;
	}
}
function assemblytag($val, $tag, $flags) {
	$val['Type'] = 'assembly';
	return $val;
}
function datatag($val, $tag, $flags) {
	$val['Type'] = 'data';
	return $val;
}
function emptytag($val, $tag, $flags) {
	$val['Type'] = 'empty';
	return $val;
}
function structtag($val, $tag, $flags) {
	$val['Type'] = 'struct';
	return $val;
}
function scripttag($val, $tag, $flags) {
	$val['Type'] = 'script';
	return $val;
}
function inttag($val, $tag, $flags) {
	$val['Type'] = 'int';
	return $val;
}
function arraytag($val, $tag, $flags) {
	$val['Type'] = 'array';
	return $val;
}
function pointertag($val, $tag, $flags) {
	$val['Type'] = 'pointer';
	return $val;
}
function bitfieldtag($val, $tag, $flags) {
	$val['Type'] = 'bitfield';
	return $val;
}
function unknowntag($val, $tag, $flags) {
	$val['Type'] = 'unknown';
	return $val;
}
function tiletag($val, $tag, $flags) {
	$val['Type'] = 'tile';
	return $val;
}
function colortag($val, $tag, $flags) {
	$val['Type'] = 'color';
	return $val;
}
?>
