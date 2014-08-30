<?php
use Falco\Falco as F;

class MapTest extends PHPUnit_Framework_TestCase {

	public function testMap1() {
		$actual = F::map(F::addBy(1), range(-2, 2));
		$this->assertEquals(range(-1, 3), $actual);
	}

	/**
	 * Map allows N array arguments and will iterate each simultaneously, up
	 * until any of the N arrays runs out of elements.
	 *
	 * This test exploits that by creating arrays of (10 - $n) elements, and
	 * shuffling them, so that the shortest array could be anywhere.
	 */
	public function testMapN() {

		$r10  = F::repeat(10);
		$data = array();

		foreach (range(0, 10) as $n) {

			$data[] = $r10(10 - $n);
			shuffle($data);

			$rs = F::apply(F::map(F::sum()), $data);

			$sum = F::sum($rs);

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

	public function testMapkv1() {
		$data = range(-2, 2);

		$actual = F::mapkv(function ($kv) { return ++$kv[1]; }, $data);

		$this->assertEquals(range(-1, 3), $actual);
	}

	/**
	 * Map allows N array arguments and will iterate each simultaneously, up
	 * until any of the N arrays runs out of elements.
	 *
	 * This test exploits that by creating arrays of (10 - $n) elements, and
	 * shuffling them, so that the shortest array could be anywhere.
	 */
	public function testMapkvN() {

		$kvSum = function () {
			$args = func_get_args();
			$sum = 0;
			foreach ($args as $arg) {
				$sum += $arg[1];
			}
			return $sum;
		};

		$r10  = F::repeat(10);
		$data = array();

		foreach (range(0, 10) as $n) {

			$data[] = $r10(10 - $n);
			shuffle($data);

			$rs  = F::apply(F::mapkv($kvSum), $data);

			$sum = F::sum($rs);
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
