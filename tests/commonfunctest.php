<?php
require_once 'libs/commonfunctions.php';
class commonfuncTest extends PHPUnit_Framework_TestCase {
	public function testGameTitle() {
		$this->assertEquals('Game', gametitle(array('Title' => 'Game')));
		$this->assertEquals('Game (USA)', gametitle(array('Title' => 'Game', 'Country' => 'USA')));
		$this->assertEquals('Game (v1.0)', gametitle(array('Title' => 'Game', 'Version' => '1.0')));
		$this->assertEquals('Game (USA v1.0)', gametitle(array('Title' => 'Game', 'Version' => '1.0', 'Country' => 'USA')));
		try {
			gametitle(null);
			gametitle(array('Country' => 'USA'));
			$this->fail('Exception not thrown');
		} catch (InvalidArgumentException $e) {
		} catch (Exception $e) {
			$this->fail('Inappropriate exception thrown');
		}
	}
	public function testMemoryData() {
		$vals = array(1,0,2,1,0);
		$memfilter = new memoryData();
		$memfilter->setData($vals);
		$this->assertEquals(1, $memfilter->getByte());
		$this->assertEquals(0, $memfilter->getByte());
		$memfilter->seekTo(0);
		$this->assertEquals(1, $memfilter->getByte());
		$memfilter->seekTo(0);
		$this->assertEquals(1, $memfilter->getShort());
		$memfilter->seekTo(0);
		$this->assertEquals(16908289, $memfilter->getLong());
		$this->assertEquals(4, $memfilter->currentOffset());
		$memfilter->seekTo(0);
		$this->assertEquals(1, $memfilter->getVar(2));
	}
}
?>