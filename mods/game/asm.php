<?php
require_once 'cpus/cpufactory.php';
class asm extends gamemod {
	private $cpucore;
	private $offset;
	
	public function getDescription() {
		return getDescription($this->offset);
	}
	private function initCPU($proc) {
		if (!isset($this->cpucore)) {
			$this->cpucore = cpuFactory::getCPU($proc);
			$this->cpucore->setPlatform($this->platform);
		}
	}
	public function execute($arg) {
		$this->offset = $arg;
		$this->initCPU($this->game['processor']);
		if ($this->offset == -1)
			$this->offset = $this->cpucore->getDefault();
		$output = $this->cpucore->execute($this->offset);
		$this->metadata['opcodes'] = $this->cpucore->getOpcodes();
		$this->metadata['nextoffset'] = decimal_to_function($this->cpucore->getCurrentOffset());
		
		$this->metadata['addrformat'] = $this->cpucore->addressFormat();
		$this->metadata['opcodeformat'] = $this->cpucore->opcodeFormat();
			
		if (isset($this->addresses[$this->offset]['arguments']))
			$this->metadata['comments'] = $this->addresses[$this->offset]['arguments'];

		$i = 0;
		foreach ($this->cpucore->getBranches() as $branch) {
			$label = 'UNKNOWN'.($i++);
			if (isset($this->addresses[$this->offset]['labels'][$this->offset - $branch]))
				$label = $this->addresses[$this->offset]['labels'][$this->offset - $branch];
			foreach ($output as $k=>$v) {
				if (isset($v['offset']) && ($v['offset'] == $branch)) {
					array_splice($output, $k, 0, array(array('label' => $label)));
					break;
				}
			}
			$this->metadata['menuitems'][$label] = $label;
		}
		
		return array($output);
	}
	public function getTemplate() {
		if (!isset($this->cpucore)) {
			if (!isset($this->game))
				throw new Exception('cannot get template without loaded game data');
			$this->initCPU($this->game['processor']);
		}
		return $this->cpucore->getTemplate();
	}
}
?>