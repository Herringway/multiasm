<?php
class logout {
	const magic = 'logout';
	function __construct() {
		if (isset($_SESSION['username']))
			session_destroy();
		header(sprintf('Location: http://%s/',$_SERVER['HTTP_HOST']));
		die();
	}
}
?>