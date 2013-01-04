<?php
class rommap extends gamemod {
	const magic = 'rommap';
	const title = 'ROM Map';
	public function execute() {
		$opts = array();
		$output = array();
		$groupbuff = array();
		foreach ($this->addresses as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			if (($this->source->identifyArea($addr) == 'rom') && !isset($data['ignore'])) {
				if (!isset($opts['collapse']))
					$output[] = array('address' => $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
				else {
					if ($data['type'] == 'assembly') {
						if ($groupbuff == array()) {
							$groupbuff['name'] = 'assembly';
							$groupbuff['type'] = $data['type'];
							$groupbuff['description'] = '';
							$groupbuff['address'] = $addr;
							$groupbuff['size'] = 0;
						}
						$groupbuff['size'] += $data['size'];
					} else if (isset($data['group'])) {
						if ($groupbuff == array()) {
							$groupbuff['name'] = $data['group'];
							$groupbuff['type'] = $data['type'];
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
		return array($output);
	}
	public function getTemplate() {
		return 'rommap';
	}
}
?>