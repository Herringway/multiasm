<?php
class rammap extends gamemod {
	const magic = 'rammap';
	const title = 'RAM Map';
	public function execute() {
		global $addresses, $platform;
		$output = array();
		foreach ($addresses as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			try {
				if ($platform->isRAM($addr) && !isset($data['ignore']))
					$output[] = array('address' => $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
			} catch (Exception $e) { }
		}
		return array($output);
	}
}
?>