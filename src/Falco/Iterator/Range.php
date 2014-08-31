<?php
namespace Falco\Iterator;

class Range implements \Iterator
{
    private $key;
    private $curr;
    private $from;
    private $to;
    private $step;

    public function __construct($from, $to, $step)
    {
        $this->from = $from;
        $this->to   = $to;
        $this->step = $step;
    }

    public function rewind()
    {
        $this->curr = $this->from;
        $this->key = 0;
    }

    public function valid()
    {
        return $this->curr <= $this->to;
    }

    public function current()
    {
        return $this->curr;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->curr += $this->step;
        $this->key++;
    }
}
