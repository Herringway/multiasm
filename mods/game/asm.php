<?php
require_once 'cpus/cpufactory.php';
class asm extends gamemod {
	private $cpucore;
	private $offset;
	
	public function description() {
		return getDescription($this->offset);
	}
	public function execute($arg) {
		$this->offset = $arg;
		global $metadata, $addresses, $platform, $godpowers, $opts, $realdesc;
		$this->cpucore = cpuFactory::getCPU('65c816');
		$this->cpucore->setPlatform($platform);
		$this->cpucore->setDataSource($platform);
		if ($this->offset === null)
			$this->offset = $this->cpucore->getDefault();
		$output = $this->cpucore->execute($this->offset);
		$metadata['nextoffset'] = decimal_to_function($this->cpucore->currentoffset);
		
		$metadata['addrformat'] = $this->cpucore->addressFormat();
		$metadata['opcodeformat'] = $this->cpucore->opcodeFormat();
			
		if (isset($addresses[$this->offset]['arguments']))
			$metadata['comments'] = $addresses[$this->offset]['arguments'];
			
		if (!isset($addresses[$this->offset]['labels']))
			$addresses[$this->offset]['labels'] = $this->cpucore->branches;
		if (isset($addresses[$this->offset]['labels']))
			foreach ($addresses[$this->offset]['labels'] as $branch)
				$metadata['menuitems'][$branch] = $branch;
			
		$metadata['form']['options'][] = array('adminonly' => true, 'label' => 'Name', 'type' => 'text', 'id' => 'name', 'value' => getOffsetName($this->offset, true));
		$metadata['form']['options'][] = array('adminonly' => true, 'label' => 'Desc', 'type' => 'text', 'id' => 'desc', 'value' => getDescription($this->offset, true));
		$metadata['form']['options'][] = array('adminonly' => true, 'label' => 'Size', 'type' => 'text', 'id' => 'size', 'value' => isset($opts['size']) ? $opts['size'] : '');
		$metadata['form']['options'][] = array('adminonly' => true, 'label' => 'Write to file', 'type' => 'checkbox', 'id' => 'write', 'value' => 'true');
		
		$metadata['form']['options'] = array_merge($metadata['form']['options'], $this->cpucore->getOptions());
		
		if (isset($opts['write']) && ($godpowers))
			$this->saveData();
		return array($output);
	}
	public static function shouldhandle($offset) {
		global $addresses;
		if (!isset($addresses[$offset]['type']) || ($addresses[$offset]['type'] !== 'data'))
			return true;
		return false;
	}
	public function getTemplate() {
		return $this->cpucore->getTemplate();
	}
}
?>