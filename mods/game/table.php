<?php
class table extends gamemod {
	private $offset;
	private $pointerblocks = array();
	public function getDescription() {
		return getDescription($this->offset);
	}
	public function getTemplate() {
		return 'table';
	}
	public function execute($arg) {
		$this->offset = $arg;
		$table = $this->addresses[$this->offset];
		
		$this->platform->seekTo($this->offset);
		
		$entries = $this->read_table($table['size'], $table['entries'], true, isset($table['terminator']) ? $table['terminator'] : null);
		
		$this->metadata['nextoffset'] = decimal_to_function($this->offset);
		$i = 0;
		$branchformat = 'UNKNOWN%0'.ceil(log(count($entries),10)).'d';
		foreach ($entries as $key => $branch) {
			if (isset($branch['Name']) && (trim($branch['Name']) != ''))
				$label = $branch['Name'];
			else
				$label = sprintf($branchformat, $i++);
			if (isset($this->addresses[$this->offset]['labels'][$this->offset - $key]))
				$label = $this->addresses[$this->offset]['labels'][$this->offset - $key];
			$this->metadata['menuitems'][$key] = $label;
		}
		return array($table['entries'], $entries);
	}
	private function read_table($tablesize, $entries, $offsetkeys = true, $terminator = null) {
		require_once 'mods/game/table/basetypes.php';
		$output = array();
		$offsets = array();
		$initialoffset = $this->platform->currentOffset();
		$i = 0;
		while (true) {
			$tmpoffset = $this->platform->currentOffset();
			debugvar($tmpoffset-$initialoffset, 'reloffset');
			debugvar($tablesize, 'size');
			if (($tablesize > -1) && ($tablesize <= $tmpoffset-$initialoffset))
				break;
			$tmparray = array();
			$ints = array();
			foreach ($entries as $entry) {
				if ($i++ > 0x10000)
					break 2;
				if (isset($entry['size']))
					$entry['size'] = eval('return '.str_replace(array_keys($ints), $ints, $entry['size']).';');
				$size = isset($entry['size']) ? $entry['size'] : 0;
				
				$value = $this->getValue(isset($entry['type']) ? $entry['type'] : 'int', $entry, $size);
				
				if (isset($entry['name'])) {
					if (!isset($entry['type']) || ($entry['type'] == 'int'))
						$ints[$entry['name']] = $value;
					$tmparray[$entry['name']] = $value;
					
				} else {
					$tmparray[] = $value;
				}
				if ($value === $terminator) {
					debugvar($tmparray[$entry['name']], 'val');
					debugvar($terminator, 'terminator');
					break 2;
				}
			}
			if ($offsetkeys)
				$output[$tmpoffset] = $tmparray;
			else 
				$output[] = $tmparray;
		}
		return $output;
	}
	private function getValue($type, $entry, $size) {
		if (file_exists($type.'.php'))
			require_once 'mods/game/table/'.$type.'.php';
		if (!class_exists('table_'.$type))
			throw new Exception($type.' is unimplemented!');
		$type = 'table_'.$type;
		$valmod = new $type($this->platform, $this->game, $entry);
		if ($valmod instanceof table_data) { }
		else
			throw new Exception('Potential class name conflict');
		return $valmod->getValue();
		/*
		switch ($type) {
			case 'bitfield':
				return $this->platform->read_bit_field($entry['size'],$entry['bitvalues']);
			break;
			case 'palette':
				return $this->platform->read_palette($entry['size']);
			break;
			case 'boolean':
				return $this->platform->getVar($entry['size']) ? true : false;
			break;
			case 'tile':
				return $this->platform->read_tile($entry['bpp'], isset($entry['palette']) ? $entry['palette'] : -1, !isset(Main::get()->opts['yaml']));
			break;
			case 'table':
				$size = 0;
				return $this->read_table(-1, $entry['entries'], false, isset($entry['terminator']) ? $entry['terminator'] : null);
			break;
		}*/
		return 0;
	}
}
?>