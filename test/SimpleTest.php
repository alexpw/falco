<?php
use Falco\Core as F;

class SimpleTest extends PHPUnit_Framework_TestCase {

	public function testIdentity() {

		$moe = array('name' =>'moe');
		$moe_obj = (object) $moe;
		$this->assertEquals($moe, F::identity($moe));
		$this->assertEquals($moe_obj, F::identity($moe_obj));

		// docs
		$moe = array('name'=>'moe');
		$this->assertTrue($moe === F::identity($moe));
	}
}
