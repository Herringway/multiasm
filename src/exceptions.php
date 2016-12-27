<?php
class UnloadedDataException extends Exception {
	public function __construct($type) {
		parent::__construct(sprintf("%s data is not loaded!",$type));
	}
}
class FileNotFoundException extends Exception {
	public function __construct($path) {
		parent::__construct(sprintf("Could not find file (path: %s)",$path));
	}
}
?>