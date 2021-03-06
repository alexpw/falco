<?php
namespace Falco\Iterator;

class TakeUntil extends \IteratorIterator
{
    private $fn;
    private $done;

    public function __construct($fn, $iter)
    {
        parent::__construct($iter);
        $this->fn = $fn;
    }

    public function rewind()
    {
        $this->done = false;
        parent::rewind();
    }

    public function valid()
    {
        if (! $this->done) {
            $this->done = call_user_func($this->fn, $this->current());
            return true;
        }
        return false;
    }
}
