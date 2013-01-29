<?php
class rammap extends gamemod {
	const magic = 'rammap';
	const title = 'RAM Map';
	public function execute() {
		$output = array();
		foreach (addressFactory::getAddresses() as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			try {
				if ($this->source->identifyArea($addr) == 'ram')
					$output[$addr] = array('address' => $addr, 'type' => isset($data['Type']) ? $data['Type'] : 'unknown', 'name' => !empty($data['Name']) ? $data['Name'] : '', 'description' => isset($data['Description']) ? $data['Description'] : '', 'size' => isset($data['Size']) ? $data['Size'] : 0);
			} catch (Exception $e) { }
		}
		ksort($output);
		return $output;
	}
	public function getTemplate() {
		return 'rommap';
	}
}
?>