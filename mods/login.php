<?php
class login {
	const magic = 'login';
	function __construct() {
		global $openid;
		if (isset($openid) && !isset($_SESSION['username'])) {
			$openid->identity = 'https://www.google.com/accounts/o8/id';
			$openid->returnUrl = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?coremod=login&param=step2';
			$openid->required = array('contact/email');
			header('Location: ' . $openid->authUrl());
		}
	}
}
?>