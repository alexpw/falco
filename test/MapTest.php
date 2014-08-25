<?php

use Falco\F as F;

class MapTest extends PHPUnit_Framework_TestCase {

	public function testMap1() {
		$actual = F::map(F::addBy(1), range(-2, 2));
		$this->assertEquals(range(-1, 3), $actual);
	}

	public function testMapN() {

		$r10  = F::repeat(10);
		$data = array();

		foreach (range(0, 4) as $n) {

			$data[] = $r10(10 - $n);

			$rs = F::apply(F::map(F::sum()), $data);

			$sum = array_sum($rs);

			/*
				1 * (100 - (10 * 0)) => 100
				2 * (100 - (10 * 1)) => 180
				3 * (100 - (10 * 2)) => 270
				4 * (100 - (10 * 3)) => 360
			*/
			$expected = count($data) * (100 - (10 * $n));

			$this->assertEquals($expected, $sum, $n);
		}
	}
}
