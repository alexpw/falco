<?php
use Falco\Core as F;

class CycleTest extends PHPUnit_Framework_TestCase
{
    public function testCycle()
    {
        $c123 = F::cycle(array(1,2,3));

        $this->assertEquals(array(1),     F::value(F::take(1, $c123)), 1);
        $this->assertEquals(array(1,2),   F::value(F::take(2, $c123)), 2);
        $this->assertEquals(array(1,2,3), F::value(F::take(3, $c123)), 3);

        $this->assertEquals(array(1,2,3,1),     F::value(F::take(4, $c123)), 4);
        $this->assertEquals(array(1,2,3,1,2),   F::value(F::take(5, $c123)), 5);
        $this->assertEquals(array(1,2,3,1,2,3), F::value(F::take(6, $c123)), 6);
    }
}
