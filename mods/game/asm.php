<?php
require_once 'cpus/cpufactory.php';
class asm extends gamemod {
	private $cpucore;
	private $offset;
	
	public function getTemplate() {
		if (!isset($this->cpucore)) {
			if (!isset($this->game))
				throw new Exception('cannot get template without loaded game data');
			$this->initCPU($this->game['Processor']);
		}
		return $this->cpucore->getTemplate();
	}
	public function init($arg) {
		$this->offset = $arg;
		$this->initCPU($this->game['Processor']);
		if ($this->offset == -1)
			$this->offset = $this->cpucore->getDefault();
		$this->source->seekTo($this->offset);
		if (isset($this->address['Size']))
			$this->cpucore->setBreakPoint($this->offset + $this->address['Size']);
	}
	public function execute($arg) {
		$output = $this->cpucore->execute($this->offset);
		$this->metadata['opcodes'] = $this->cpucore->getOpcodes();
		
		$this->metadata['addrformat'] = $this->cpucore->addressFormat();
		$this->metadata['opcodeformat'] = $this->cpucore->opcodeFormat();
			
		if (isset($this->address['Arguments']))
			$this->metadata['comments'] = $this->address['Arguments'];
			

		$i = 0;
		$branches = $this->cpucore->getBranches();
		$labels = array();
		if (isset($this->address['Labels']))
			foreach ($this->address['Labels'] as $label => $name)
				if (!in_array($label + $this->offset, $branches))
					$branches[] = $label + $this->offset;
		sort($branches);
		foreach ($branches as $branch) {
			$label = 'UNKNOWN'.($i++);
			if (isset($this->address['Labels'][$branch - $this->offset]))
				$label = $this->address['Labels'][$branch - $this->offset];
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
			if (isset($opcode['target']) || isset($opcode['destination'])) {
				$addr = isset($opcode['target']) ? $opcode['target'] : $opcode['destination'];
				$opcode['uri'] = sprintf($this->cpucore->addressFormat(), $addr);
				$targEntry = addressFactory::getAddressSubentryFromOffset($addr);
				if (isset($targEntry['Subname']) && isset($targEntry['Name'])) {
					$opcode['name'] = $targEntry['Subname'];
					$opcode['uri'] = $targEntry['Name'];
				} else if (isset($targEntry['Name'])) {
					$opcode['name'] = $targEntry['Name'];
					$opcode['uri'] = $targEntry['Name'];
				}
				if (($addr >= $this->offset) && ($addr < $this->source->currentOffset())) {
					$opcode['uri'] = $this->metadata['offsetname'].'#'.$labels[array_search($addr, $branches)];
					$opcode['name'] = $labels[array_search($addr, $branches)];
				}
				$opcode['comments'] = array();
				if(isset($targEntry['Description']))
					$opcode['comments']['description'] = $targEntry['Description'];
				if(isset($targEntry['Arguments']))
					$opcode['comments'] += $targEntry['Arguments'];
				
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