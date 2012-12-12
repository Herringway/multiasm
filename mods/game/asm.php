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
		global $metadata, $game;
		$this->cpucore = cpuFactory::getCPU($game['processor']);
		$this->cpucore->setPlatform($this->platform);

		if ($this->offset === null)
			$this->offset = $this->cpucore->getDefault();
		$output = $this->cpucore->execute($this->offset);
		$metadata['opcodes'] = $this->cpucore->getOpcodes();
		$metadata['nextoffset'] = decimal_to_function($this->cpucore->getCurrentOffset());
		
		$metadata['addrformat'] = $this->cpucore->addressFormat();
		$metadata['opcodeformat'] = $this->cpucore->opcodeFormat();
			
		if (isset($this->addresses[$this->offset]['arguments']))
			$metadata['comments'] = $this->addresses[$this->offset]['arguments'];
			
		if (!isset($this->addresses[$this->offset]['labels']))
			$this->addresses[$this->offset]['labels'] = $this->cpucore->getBranches();
		if (isset($this->addresses[$this->offset]['labels']))
			foreach ($this->addresses[$this->offset]['labels'] as $branch)
				$metadata['menuitems'][$branch] = $branch;
		
		//$metadata['form']['options'] = array_merge($metadata['form']['options'], $this->cpucore->getOptions());
		
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