<?php

use Falco\F as F;

class AlwaysTest extends PHPUnit_Framework_TestCase {

	public function testAlways() {
		$alwaysTrue = F::always(true);
		$this->assertTrue($alwaysTrue(true));
		$this->assertTrue($alwaysTrue(false));
		$this->assertTrue($alwaysTrue(0));
	}
}
