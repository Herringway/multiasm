<?php
require_once 'src/commonfunctions.php';
require_once 'mods/game/table/basetypes.php';
class basetypeTest extends PHPUnit_Framework_TestCase {
	public function testInt() {
		$vals = array(1,0,2,1,0);
		$memfilter = new memoryData();
		$memfilter->setData($vals);
		
		$memfilter->seekTo(0);
		$inttest = new table_int($memfilter, array(), array('Size' => 1));
		$this->assertEquals(1, $inttest->getValue());
		$this->assertEquals(1, $inttest->getValue());
		
		$memfilter->seekTo(2);
		$inttest = new table_int($memfilter, array(), array('Size' => 1, 'Base' => 2));
		$this->assertEquals(10, $inttest->getValue());
		
		$inttest = new table_int($memfilter, array(), array('Size' => 1, 'Base' => 1));
		try {
			$inttest->getValue();
			$this->fail('Impossible base was accepted');
		} catch (Exception $e) { }
		
		$memfilter->seekTo(0);
		$inttest = new table_int($memfilter, array(), array('Size' => 1, 'Values' => ['derp']));
		$this->assertEquals(1, $inttest->getValue());
		
		$memfilter->seekTo(1);
		$inttest = new table_int($memfilter, array(), array('Size' => 1, 'Values' => ['derp']));
		$this->assertEquals('derp', $inttest->getValue());
	}
}
?>
