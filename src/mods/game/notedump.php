<?php
class notedump extends gamemod {
	public function execute($type) {
		$output = array();
		foreach (addressFactory::getAddresses() as $name=>$data) {
			try {
				if (!isset($data['Offset']))
					continue;
				if ($this->source->identifyArea($data['Offset']) == $type)
					if (isset($data['Description'])) {
						$output[$data['Offset']] = ['address' => $data['Offset'], 'name' => $name, 'description' => $data['Description'], 'notes' => isset($data['Notes']) ? $data['Notes'] : '', 'size' => isset($data['Size']) ? $data['Size'] : 0];
						$total = 0;
						if (isset($data['Entries']))
							foreach ($data['Entries'] as $k=>$entry) {
								$output[$data['Offset']]['Entries'][] = ['Name' => isset($entry['Name']) ? $entry['Name'] : '', 'Size' => $entry['Size'], 'Offset' => $total, 'Description' => isset($entry['Description']) ? $entry['Description'] : '', 'Notes' => isset($entry['Notes']) ? $entry['Notes'] : ''];
								$total += $entry['Size'];
							}
						if (!isset($output[$data['Offset']]['Entries']) || (count($output[$data['Offset']]['Entries']) <= 1))
							$output[$data['Offset']]['Entries'] = [];
					}
			} catch (Exception $e) {}
		}
		ksort($output);
		return $output;
	}
	public static function getMagicValue() { return 'notedump'; }
	public static function getMenuEntries($source) {
		$output = array();
		return $output;
	}
	public function getDescription() { return ' notes'; }
	public function getTemplate() { return 'notedump'; }
}
?>