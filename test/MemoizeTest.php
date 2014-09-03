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
                if ($n <= 2) {
                    return 1;
                }
                return $memoFib($n - 1) + $memoFib($n - 2);
            },
            F::identity()
        );

        foreach (range(1,100) as $i) {
            $this->assertEquals($i - 1, $numCalls);
            $memoFib($i);
        }
    }
}
