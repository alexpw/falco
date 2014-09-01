<?php
use Falco\Core as F;

class PartitionByTest extends PHPUnit_Framework_TestCase
{
    public function testPartitionByOdd()
    {
        $p = F::partitionBy(F::isOdd());

        $in  = array();
        $out = array();
        foreach (range(1,10) as $i) {
            $in[]  = $i;
            $out[] = array($i);
            $this->assertEquals($out, F::value($p($in)),          "strict $i");
            $this->assertEquals($out, F::value($p(F::lazy($in))), "lazy $i");
        }
    }
}
