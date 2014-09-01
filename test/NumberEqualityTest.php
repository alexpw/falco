<?php
use Falco\Core as F;

class NumeberEqualityTest extends PHPUnit_Framework_TestCase
{
    public function numberProvider()
    {
        return array(
            array(-2),
            array(-1),
            array(0),
            array(1),
            array(2),
        );
    }

    /**
     * @dataProvider numberProvider
     */
    public function testOdd($a)
    {
        $this->assertEquals(F::isOdd($a), (abs($a) % 2 === 1));
    }

    /**
     * @dataProvider numberProvider
     */
    public function testEven($a)
    {
        $this->assertEquals(F::isEven($a), (abs($a) % 2 === 0));
    }

    /**
     * @dataProvider numberProvider
     */
    public function testZero($a)
    {
        $this->assertEquals(F::isZero($a), $a === 0);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testPositive($a)
    {
        $this->assertEquals(F::isPositive($a), $a > 0);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testNegative($a)
    {
        $this->assertEquals(F::isNegative($a), $a < 0);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testTruthy($a)
    {
        $this->assertEquals(F::isTruthy($a), !!$a);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testFalsy($a)
    {
        $this->assertEquals(F::isFalsy($a), !$a);
    }

    /**
     * @dataProvider numberProvider
     */
    public function testEmptyNumber($a)
    {
        $this->assertEquals(F::isEmpty($a), empty($a));
    }
}
