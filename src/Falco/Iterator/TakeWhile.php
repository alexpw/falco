<?php
namespace Falco\Iterator;

class TakeWhile extends \IteratorIterator
{
    private $fn;

    public function __construct($fn, $iter)
    {
        parent::__construct($iter);
        $this->fn = $fn;
    }

    public function valid()
    {
        return call_user_func($this->fn, $this->current()) === true;
    }
}
