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

	public function testAddBy() {
		$x_range = range(-100, 100);
		foreach (range(-10, 10) as $n) {
			$f = F::addBy($n);
			foreach ($x_range as $x) {
				$this->assertEquals($x + $n, $f($x));
			}
		}
	}
	public function testSubtractBy() {
		$x_range = range(-100, 100);
		foreach (range(-10, 10) as $n) {
			$f = F::subtractBy($n);
			foreach ($x_range as $x) {
				$this->assertEquals($x - $n, $f($x));
			}
		}
	}
	public function testMultiplyBy() {
		$x_range = range(-100, 100);
		foreach (range(-10, 10) as $n) {
			$f = F::multiplyBy($n);
			foreach ($x_range as $x) {
				$this->assertEquals($x * $n, $f($x));
			}
		}
	}
	public function testDivideBy() {
		$x_range = range(-100, 100);
		foreach (range(-10, 10) as $n) {
			if ($n === 0) continue;
			$f = F::divideBy($n);
			foreach ($x_range as $x) {
				$this->assertEquals($x / $n, $f($x));
			}
		}
	}
}
