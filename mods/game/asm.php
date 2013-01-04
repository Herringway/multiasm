<?php
require_once 'cpus/cpufactory.php';
class asm extends gamemod {
	private $cpucore;
	private $offset;
	
	public function getTemplate() {
		if (!isset($this->cpucore)) {
			if (!isset($this->game))
				throw new Exception('cannot get template without loaded game data');
			$this->initCPU($this->game['processor']);
		}
		return $this->cpucore->getTemplate();
	}
	public function init($arg) {
		$this->offset = $arg;
		$this->initCPU($this->game['processor']);
		if ($this->offset == -1)
			$this->offset = $this->cpucore->getDefault();
		$this->source->seekTo($this->offset);
		if (isset($this->address['size']))
			$this->cpucore->setBreakPoint($this->offset + $this->address['size']);
	}
	public function execute($arg) {
		$output = $this->cpucore->execute($this->offset);
		$this->metadata['opcodes'] = $this->cpucore->getOpcodes();
		
		$this->metadata['addrformat'] = $this->cpucore->addressFormat();
		$this->metadata['opcodeformat'] = $this->cpucore->opcodeFormat();
			
		if (isset($this->address['arguments']))
			$this->metadata['comments'] = $this->address['arguments'];
			

		$i = 0;
		$branches = $this->cpucore->getBranches();
		sort($branches);
		foreach ($branches as $branch) {
			$label = 'UNKNOWN'.($i++);
			if (isset($this->address['labels'][$this->offset - $branch]))
				$label = $this->address['labels'][$this->offset - $branch];
			$labels[] = $label;
			foreach ($output as $k=>$v) {
				if (isset($v['offset']) && ($v['offset'] == $branch)) {
					array_splice($output, $k, 0, array(array('label' => $label)));
					break;
				}
			}
			$this->metadata['menuitems'][$label] = $label;
		}
		foreach($output as &$opcode) {
			if (isset($opcode['target'])) {
				$opcode['uri'] = sprintf($this->cpucore->addressFormat(), $opcode['target']);
				if (isset($this->addresses[$opcode['target']]['name'])) {
					$opcode['name'] = $this->addresses[$opcode['target']]['name'];
					$opcode['uri'] = $this->addresses[$opcode['target']]['name'];
				}
				if (($opcode['target'] >= $arg) && ($opcode['target'] < $this->source->currentOffset())) {
					$opcode['uri'] = $this->metadata['offsetname'].'#'.$labels[array_search($opcode['target'], $branches)];
					$opcode['name'] = $labels[array_search($opcode['target'], $branches)];
				}
				$opcode['comments'] = array();
				if(isset($this->addresses[$opcode['target']]['description']))
					$opcode['comments']['description'] = $this->addresses[$opcode['target']]['description'];
				if(isset($this->addresses[$opcode['target']]['arguments']))
					$opcode['comments'] += $this->addresses[$opcode['target']]['arguments'];
				
			}
		}
		return array($output);
	}
	private function initCPU($proc) {
		if (!isset($this->cpucore)) {
			$this->cpucore = cpuFactory::getCPU($proc);
			$this->cpucore->setPlatform($this->source);
		}
	}
}
?>