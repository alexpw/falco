<?php

use Falco\F as F;

class ComposeTest extends PHPUnit_Framework_TestCase {

	public function testCompose1() {
		$f = F::compose(F::isEmpty());
		$this->assertTrue($f(''));
	}

	public function testComposeN() {

		$fns = array(F::isEven());

		foreach (range(2, 40, 2) as $n) {

			$fns[] = F::addBy($n);

			$f = F::apply(F::compose(), $fns);

			$this->assertTrue($f(2));
			$this->assertTrue($f(4));
		}
	}
}
