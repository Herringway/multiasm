<?php
class Penguin_Twig_Extensions extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            'gravatar' => new Twig_Filter_Method($this, 'gravatar'),
			'yaml' => new Twig_Filter_Method($this, 'yaml', array('is_safe' => array('html'))),
        );
    }
	public function getName() {
		return 'Penguins Extensions';
	}

    public function gravatar($email, $default = 'retro', $size = 100, $rating = 'g') {
		return sprintf("http://www.gravatar.com/avatar/%s?d=%s&s=%s&r=%s", md5(strtolower(trim($email))),urlencode($default),$size, $rating);
	}
	public function yaml($data) {
		$output = yaml_emit($data, YAML_UTF8_ENCODING);
		$output = preg_replace_callback('/^(\d+):/m', array($this, 'hexafixer_human'), $output);
		return $output;
	}
	private function hexafixer_human($matches) {
		static $i = 0;
		return sprintf('<a href="#%X" name="%X">%d (%X</a>)', $matches[1], $matches[1], $i++, $matches[1]);
	}
}
?>