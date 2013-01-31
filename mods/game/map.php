<?php
class map extends gamemod {
	private $type;
	public function init($type) { $this->type = $type; }
	public function execute($type) {
		$output = array();
		foreach (addressFactory::getAddresses() as $name=>$data) {
			try {
				if ($this->source->identifyArea($data['Offset']) == $type)
					$output[$data['Offset']] = array('address' => $data['Offset'], 'type' => isset($data['Type']) ? $data['Type'] : 'unknown', 'name' => $name, 'description' => isset($data['Description']) ? $data['Description'] : '', 'size' => isset($data['Size']) ? $data['Size'] : 0);
			} catch (Exception $e) {}
		}
		ksort($output);
		return $output;
	}
	public static function getMagicValue() { return 'map'; }
	public static function getMenuEntries($source) {
		$output = array();
		foreach($source->getSources() as $source)
			$output['map/'.$source] = strtoupper($source).' Map';
		return $output;
	}
	public function getDescription() { return strtoupper($this->type).' Map'; }
	public function getTemplate() { return 'rommap'; }
}
?>