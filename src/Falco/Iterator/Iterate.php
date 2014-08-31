<?php
namespace Falco\Iterator;

class Iterate implements \Iterator
{
    private $fn;
    private $x;
    private $original;
    private $key;

    public function __construct($fn, $x)
    {
        $this->fn       = $fn;
        $this->original = $x;
    }

    public function rewind()
    {
        $this->key = 0;
        $this->x = $this->original;
    }

    public function valid()
    {
        return true;
    }

    public function current()
    {
        return $this->x;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        $this->key++;
        $this->x = call_user_func($this->fn, $this->x);
    }
}
