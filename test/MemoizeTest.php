<?php
use Falco\Core as F;

class MemoizeTest extends PHPUnit_Framework_TestCase
{
    public function testMemoFib()
    {
        $numCalls = 0;
        $memoFib = F::memoize(
            function ($n) use (& $memoFib, & $numCalls) {
                $numCalls++;
                if ($n === 1 || $n === 2) return 1;
                return $memoFib($n - 1) + $memoFib($n - 2);
            },
            F::identity()
        );

        #foreach (range(0,1) as $i) {
        #    $this->assertEquals($i, $numCalls);
        #    $memoFib($i);
        #}
        #$this->assertEquals(array(1),     F::value(F::take(1, $c123)), 1);
    }
}
