<?php
class login {
	const magic = 'login';
	function __construct() {
		global $display, $dataname, $game;
		$display->displaydata['title'] = 'Login';
		$display->displaydata['routinename'] = '';
		$display->displaydata['offsetname'] = '';
		$display->displaydata['game'] = 'login';
		$display->mode = 'login';
		$display->display('');
	}
}
?>