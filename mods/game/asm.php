<?php
require_once 'src/cpuFactory.php';
class asm extends gamemod {
	private $cpucore;
	private $offset;
	
	public function getTemplate() {
		if (!isset($this->cpucore)) {
			if (!isset($this->game))
				throw new Exception('cannot get template without loaded game data');
			$this->initCPU($this->game['CPU'], $this->game['Platform']);
		}
		return $this->cpucore->getTemplate();
	}
	public function init($arg) {
		$this->offset = $arg;
		$this->initCPU($this->game['CPU'], $this->game['Platform']);
		if ($this->offset == -1)
			$this->offset = $this->cpucore->getDefault();
		$this->source->seekTo($this->offset);
		if (isset($this->address['Size']))
			$this->cpucore->setBreakPoint($this->offset + $this->address['Size'],'definedsize');
		addressFactory::loadPlatformAddresses($this->game['id']);
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
		$dumpbranches = false;
		if ($dumpbranches) {
			foreach ($branches as $i=>$branch)
				$branchcopy[$branch - $this->offset] = $labels[$i];
			echo yaml_emit($branchcopy);
			die;
		}
		$ioffs = $this->source->currentOffset();
		foreach($output as &$opcode) {
			if (isset($opcode['stack'])) {
				if (isset($this->address['Locals'][$opcode['stack']])) {
					$opcode['uri'] = $opcode['name'] = $this->address['Locals'][$opcode['stack']];
				}
			}
			if (isset($opcode['target']) || isset($opcode['destination'])) {
				$addr = isset($opcode['target']) ? $opcode['target'] : $opcode['destination'];
				debugvar($addr, 'Searching for...');
				$opcode['uri'] = sprintf($this->cpucore->addressFormat(), $addr);
				$targEntry = addressFactory::getAddressSubentryFromOffset($addr, $this->source, $this->game);
				$opcode['name'] = $targEntry['Name'];
				$opcode['uri'] = $targEntry['Name'];
				if (isset($targEntry['Type']) && ($targEntry['Type'] != 'assembly')) {
					if (isset($targEntry['Subname']) || isset($targEntry['Index']))
						$opcode['uri'] .= '#';
					if (isset($targEntry['Count']))
						$opcode['name'] .= '['.$targEntry['Count'].']';
					if (isset($targEntry['Subname'])) {
						$opcode['name'] .= '.'.$targEntry['Subname'];
						$opcode['uri'] .= $targEntry['Subname'];
					}
					if (isset($targEntry['Index'])) {
						$opcode['name'] .= '['.$targEntry['Index'].']';
						$opcode['uri'] .= $targEntry['Index'];
					}
				} else if (!isset($targEntry['Type']) || ($targEntry['Type'] == 'assembly')) {
					if (isset($targEntry['Subname'])) {
						$opcode['name'] .= '#'.$targEntry['Subname'];
						$opcode['uri'] .= '#'.$targEntry['Subname'];
					}
				}
				if (isset($targEntry['Count']))
					$opcode['uri'] = sprintf('%s#'.$this->cpucore->addressFormat(), $targEntry['Name'], $targEntry['Count']);
				if (($addr >= $this->offset) && ($addr < $ioffs)) {
					$opcode['uri'] = $this->metadata['offsetname'].'#'.$labels[array_search($addr, $branches)];
					$opcode['name'] = $labels[array_search($addr, $branches)];
				}
				$opcode['comments'] = array();
				$opcode['comments']['Offset'] = sprintf('0x'.$this->cpucore->addressFormat(), $addr);
				if(isset($targEntry['Description']))
					$opcode['comments']['description'] = $targEntry['Description'];
				if(isset($targEntry['Notes']))
					$opcode['comments']['Note'] = $targEntry['Notes'];
				if(isset($targEntry['Values']))
					$opcode['comments']['Values'] = $targEntry['Values'];
				if(isset($targEntry['Arguments']))
					$opcode['comments']['Arguments'] = $targEntry['Arguments'];
				if(isset($targEntry['Return Values']))
					$opcode['comments']['Returns'] = $targEntry['Return Values'];
				
			}
		}
		return array($output);
	}
	private function initCPU($proc, $platform) {
		if (!isset($this->cpucore)) {
			$this->cpucore = cpuFactory::getCPU($platform);
			foreach ($proc as $opt=>$val)
				$this->cpucore->setState($opt, $val);
			$this->cpucore->setPlatform($this->source);
		}
	}
}
?>
