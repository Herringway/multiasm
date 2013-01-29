<?php
class rommap extends gamemod {
	const magic = 'rommap';
	const title = 'ROM Map';
	public function execute() {
		$opts = array();
		$output = array();
		$groupbuff = array();
		foreach (addressFactory::getAddresses() as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			if ($this->source->identifyArea($addr) == 'rom') {
				if (!isset($opts['collapse']))
					$output[] = array('address' => $addr, 'type' => isset($data['Type']) ? $data['Type'] : 'unknown', 'name' => !empty($data['Name']) ? $data['Name'] : '', 'description' => isset($data['Description']) ? $data['Description'] : '', 'size' => isset($data['Size']) ? $data['Size'] : 0);
				else {
					if ($data['Type'] == 'assembly') {
						if ($groupbuff == array()) {
							$groupbuff['name'] = 'assembly';
							$groupbuff['type'] = $data['Type'];
							$groupbuff['description'] = '';
							$groupbuff['address'] = $addr;
							$groupbuff['size'] = 0;
						}
						$groupbuff['size'] += $data['size'];
					} else if (isset($data['Group'])) {
						if ($groupbuff == array()) {
							$groupbuff['name'] = $data['Group'];
							$groupbuff['type'] = $data['Type'];
							$groupbuff['description'] = '';
							$groupbuff['address'] = $addr;
							$groupbuff['size'] = 0;
						}
						$groupbuff['size'] += $data['size'];
					} else {
						if ($groupbuff != array()) {
							$output[] = $groupbuff;
							$groupbuff = array();
						}
					}
				}
			}
		}
		return $output;
	}
	public function getTemplate() {
		return 'rommap';
	}
}
?>