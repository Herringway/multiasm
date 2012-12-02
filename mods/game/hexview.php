<?php
class hexview extends gamemod {
	public function execute() {
		require 'libs/hexview.php';
		global $addresses, $offset, $game, $metadata, $platform, $rom;
		if (!isset($addresses[$offset]['size']))
			die('Data has no size defined!');
		
		$metadata['nextoffset'] = decimal_to_function($offset+$addresses[$offset]['size']);
		if (isset($addresses[$offset]['charset']))
			$charset = $game['texttables'][$addresses[$offset]['charset']]['replacements'];
		else if (isset($game['defaulttext']))
			$charset = $game['texttables'][$game['defaulttext']]['replacements'];
		else
			$charset = null;
		if (isset($addresses[$offset]['description']))
			$metadata['description'] = $addresses[$offset]['description'];
		$rom->seekTo($platform->map_rom($offset));
		return hexview($rom->read($addresses[$offset]['size']), isset($addresses[$offset]['width']) ? $addresses[$offset]['width'] : 16, $offset, $charset);
	}
	public static function shouldhandle() {
		global $offset, $addresses;
		if (isset($addresses[$offset]['type']) && (($addresses[$offset]['type'] === 'data') || ($addresses[$offset]['type'] === 'empty')) && !isset($addresses[$offset]['entries']))
			return true;
		return false;
	}
}
?>