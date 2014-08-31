<?php

use Falco\Core as F;

class EqualityTest extends PHPUnit_Framework_TestCase {

	public function testAll() {
		$this->assertFalse(F::all(F::isOdd(), [1,2,3,4]));
		$this->assertTrue(F::all(F::isOdd(), [1,3,5]));
	}
	public function testAny() {
		$this->assertTrue(F::any(F::isOdd(), [1,2,3,4]));
		$this->assertFalse(F::any(F::isOdd(), [2,4,6]));
	}
	public function testNone() {
		$this->assertFalse(F::none(F::isOdd(), [1,2,3,4]));
		$this->assertTrue(F::none(F::isOdd(), [2,4,6]));
	}
	public function testAnd() {
		$truthy = F::andBy(F::isTruthy());
		$this->assertTrue($truthy([1]));
		$this->assertTrue($truthy(2));
		$this->assertFalse($truthy(0));
		$this->assertFalse($truthy(false));

		$truthyOdd = F::andBy(F::isTruthy(), F::isOdd());
		$this->assertTrue($truthyOdd(1));
		$this->assertFalse($truthyOdd(2));
	}
    public function testOr() {
		$truthy = F::orBy(F::isTruthy());
		$this->assertTrue($truthy([1]));
		$this->assertTrue($truthy(2));
		$this->assertFalse($truthy(0));
		$this->assertFalse($truthy(false));

		$truthyOdd = F::orBy(F::isTruthy(), F::isOdd());
		$this->assertTrue($truthyOdd(1));
		$this->assertTrue($truthyOdd(2));
		$this->assertFalse($truthyOdd(0));
	}

	public function testEq() {
		$eq1 = F::eq(1);
		$this->assertTrue($eq1(1));
		$this->assertTrue($eq1(1, 1, 1, 1));
		$this->assertFalse($eq1(1, 1, 0, 1));
		$this->assertFalse($eq1(0));
	}
	public function testE1() {
		$eq1 = F::eq(1);
		$this->assertTrue($eq1(1));
		$this->assertTrue($eq1(1, 1, 1, 1));
		$this->assertFalse($eq1(1, 1, 0, 1));
		$this->assertFalse($eq1(0));
	}
}
