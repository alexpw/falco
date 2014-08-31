<?php
namespace Falco\Iterator;

class SkipWhile extends \FilterIterator
{
    private $iter;
    private $fn;
    private $done;

    public function __construct($fn, $iter)
    {
        parent::__construct($iter);
        $this->iter = $iter;
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

        return $this->done;
    }
}
