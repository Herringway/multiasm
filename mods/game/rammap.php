<?php
class rammap extends gamemod {
	const magic = 'rammap';
	const title = 'RAM Map';
	public function execute() {
		$output = array();
		foreach ($this->addresses as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			try {
				if (($this->platform->identifyArea($addr) == 'ram') && !isset($data['ignore']))
					$output[] = array('address' => $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		return array($output);
	}
	public function getTemplate() {
		return 'rammap';
	}
}
?>