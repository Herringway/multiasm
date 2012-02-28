<?php
class cache implements arrayaccess {
	public function clear() {
		if (function_exists('apc_clear_cache'))
			return apc_clear_cache();
		return false;
	}
	public function offsetSet($name, $object) {
		if (function_exists('apc_store'))
			return apc_store($name, $object, 0);
		return false;
    }
    public function offsetExists($name) {
		if (function_exists('apc_exists'))
			return apc_exists($name);
		return false;
    }
    public function offsetUnset($name) {
		if (function_exists('apc_delete'))
			return apc_delete($name);
		return false;
    }
    public function offsetGet($name) {
		if (function_exists('apc_fetch'))
			return apc_fetch($name);
		return false;
    }
	public function mode() {
		if (function_exists('apc_fetch'))
			return 'PHP-APC';
		return 'none';
	}
	public function stats() {
		if (function_exists('apc_cache_info'))
			return apc_cache_info();
		return null;
	}
}
?>