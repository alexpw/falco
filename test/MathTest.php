<?php

use Falco\F as F;

class MathTest extends PHPUnit_Framework_TestCase {

	public function testMin() {
		$this->assertEquals(F::min(-2, -1, 0, 1, 2), -2);
		$this->assertEquals(F::min(-1, 1, 0, 2, -2), -2);
		$xs = range(-2, 2);
		$this->assertEquals(F::min($xs), -2);
		shuffle($xs);
		$this->assertEquals(F::min($xs), -2);
	}
	public function testMax() {
		$this->assertEquals(F::max(-2, -1, 0, 1, 2), 2);
		$this->assertEquals(F::max(-1, 1, 0, 2, -2), 2);
		$xs = range(-2, 2);
		$this->assertEquals(F::max($xs), 2);
		shuffle($xs);
		$this->assertEquals(F::max($xs), 2);
	}
}
