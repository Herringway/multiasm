<?php
class rommap extends gamemod {
	const magic = 'rommap';
	const title = 'ROM Map';
	public function execute() {
		global $addresses, $platform, $opts;
		$output = array();
		$groupbuff = array();
		foreach ($addresses as $addr=>$data) {
			if (!is_numeric($addr))
				continue;
			try {
				$realaddr = $platform->map_rom($addr);
				if (($realaddr !== null) && !isset($data['ignore'])) {
					if (!isset($opts['collapse']))
						$output[] = array('address' => isset($opts['real_address']) ? $realaddr : $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
					else {
						if ($data['type'] == 'assembly') {
							if ($groupbuff == array()) {
								$groupbuff['name'] = 'assembly';
								$groupbuff['type'] = $data['type'];
								$groupbuff['description'] = '';
								$groupbuff['addr'] = $addr;
								$groupbuff['realaddr'] = $realaddr;
								$groupbuff['size'] = 0;
							}
							$groupbuff['size'] += $data['size'];
						} else if (isset($data['group'])) {
							if ($groupbuff == array()) {
								$groupbuff['name'] = $data['group'];
								$groupbuff['type'] = $data['type'];
								$groupbuff['description'] = '';
								$groupbuff['addr'] = $addr;
								$groupbuff['realaddr'] = $realaddr;
								$groupbuff['size'] = 0;
							}
							$groupbuff['size'] += $data['size'];
						} else {
							if ($groupbuff != array()) {
								$output[] = array('address' => isset($opts['real_address']) ? $groupbuff['realaddr'] : $groupbuff['addr'], 'type' => $groupbuff['type'], 'name' => $groupbuff['name'], 'description' => $groupbuff['description'], 'size' => $groupbuff['size']);
								$groupbuff = array();
							}
							$output[] = array('address' => isset($opts['real_address']) ? $realaddr : $addr, 'type' => isset($data['type']) ? $data['type'] : 'unknown', 'name' => !empty($data['name']) ? $data['name'] : '', 'description' => isset($data['description']) ? $data['description'] : '', 'size' => isset($data['size']) ? $data['size'] : 0);
						}
					}
				}
			} catch (Exception $e) { }
		}
		return array($output);
	}
}
?>