<?php
class display {
	public static function display_error($error) {
		$twig = new Twig_Environment(new Twig_Loader_Filesystem('templates'), array('debug' => $settings['debug']));
		$twig->addExtension(new Twig_Extension_Debug());
		$twig->addExtension(new Penguin_Twig_Extensions());
		echo $this->twig->render('error.tpl', array('routinename' => '', 'hideright' => true, 'title' => 'FLAGRANT SYSTEM ERROR', 'nextoffset' => '', 'game' => '', 'data' => $error, 'thisoffset' => '', 'options' => '', 'offsetname' => '', 'addrformat' => '', 'menuitems' => '', 'opcodeformat' => '', 'gamelist' => '', 'error' => 1));
	}
}
?>