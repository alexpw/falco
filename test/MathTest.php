<?php
use Falco\Core as F;

class MathTest extends PHPUnit_Framework_TestCase
{
    public function testCount()
    {
        $in = array();
        foreach (range(1,1000) as $i) {
            $in[] = $i;
            $this->assertEquals($i, F::count($in));
            $this->assertEquals($i, F::count(F::lazy($in)));
        }

        $actual = F::count(F::take(10, F::cycle([1,2,3])));
        $this->assertEquals(10, $actual);
    }

    public function testCountBy()
    {
        $odd = function ($cnt, $x) {
            return $cnt + ($x % 2 === 1)+0;
        };
        $in = array();
        foreach (range(1,1000) as $i) {
            $in[] = $i;
            $exp  = (int) ceil($i / 2);
            $this->assertEquals($exp, F::countBy($odd, $in));
            $this->assertEquals($exp, F::countBy($odd, F::lazy($in)));
        }

        $actual = F::countBy($odd, F::take(10, F::cycle([1,2,3])));
        $this->assertEquals(7, $actual);
    }

    public function testMin()
    {
        $this->assertEquals(-2, F::min(-2, -1, 0, 1, 2));
        $this->assertEquals(-2, F::min(-1, 1, 0, 2, -2));
        $xs = range(-2, 2);
        $this->assertEquals(-2, F::min($xs));
        shuffle($xs);
        $this->assertEquals(-2, F::min($xs));
    }
    public function testMax()
    {
        $this->assertEquals(2, F::max(-2, -1, 0, 1, 2));
        $this->assertEquals(2, F::max(-1, 1, 0, 2, -2));
        $xs = range(-2, 2);
        $this->assertEquals(2, F::max($xs));
        shuffle($xs);
        $this->assertEquals(2, F::max($xs));
    }

    public function testSum()
    {
        $nums = range(-100, 100);
        $this->assertEquals(array_sum($nums), F::sum($nums));
        $this->assertEquals(array_sum($nums), F::apply(F::sum(), $nums));
    }
    public function testProduct()
    {
        $nums = range(-100, 100);
        unset($nums[100]); // remove the val 0, so it doesn't ruin the product

        $this->assertEquals(array_product($nums), F::product($nums));
        $this->assertEquals(array_product($nums), F::apply(F::product(), $nums));
    }

    public function testAddBy()
    {
        $x_range = range(-100, 100);
        foreach (range(-10, 10) as $n) {
            $f = F::addBy($n);
            foreach ($x_range as $x) {
                $this->assertEquals($x + $n, $f($x));
            }
        }
    }
    public function testSubtractBy()
    {
        $x_range = range(-100, 100);
        foreach (range(-10, 10) as $n) {
            $f = F::subtractBy($n);
            foreach ($x_range as $x) {
                $this->assertEquals($x - $n, $f($x));
            }
        }
    }
    public function testMultiplyBy()
    {
        $x_range = range(-100, 100);
        foreach (range(-10, 10) as $n) {
            $f = F::multiplyBy($n);
            foreach ($x_range as $x) {
                $this->assertEquals($x * $n, $f($x));
            }
        }
    }
    public function testDivideBy()
    {
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
