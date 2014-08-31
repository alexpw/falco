<?php
namespace Falco\Iterator;

class SkipUntil extends \FilterIterator
{
    private $iter;
    private $fn;
    private $done;

    public function __construct(\Iterator $iterator, $fn)
    {
        parent::__construct($iterator);
        $this->iter = $iterator;
        $this->fn   = $fn;
        $this->done = false;
    }

    public function rewind()
    {
        $this->done = false;
        parent::rewind();
    }

    public function accept()
    {
        if ($this->done) {
            return true;
        }

        $this->done = !! call_user_func($this->fn, $this->iter->current());

        return false;
    }
}
