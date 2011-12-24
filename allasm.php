<?php
ini_set('memory_limit', '1024M');
require_once 'commonfunctions.php';
require_once 'Dwoo/dwooAutoload.php';
$gameshort = 'eb';
$game = yaml_parse_file('games/'.$gameshort.'/game.yml');
$known_addresses = yaml_parse_file('games/'.$gameshort.'/known_offsets.yml') + yaml_parse_file('cpus/'.$game['platform'].'_registers.yml');

@$handle = fopen('../rms/'.$game['rom'], 'r');
if (!$handle)
	die ('File not found!');
		
require_once 'cpus/65816_new.php';
$core = new core($handle,$game,$known_addresses);
$core->placeholdernames = true;
$isad = 0;
$instructionlist = array();
foreach ($known_addresses as $addr=>$vals)
	if (($addr >= 0xC00000) && ($vals['type'] == 'assembly'))
		$instructionlist[] = array('instructions' => $core->execute($addr,isset($vals['name']) ? $vals['name'] : dechex($addr)), 'name' => isset($vals['name']) ? $vals['name'] : sprintf('UNKNOWN_%06X', $addr));

$dwoo = new Dwoo();
$dwoo->output('templates/allasm.tpl', array('instructions' => $instructionlist));
?>