<?php namespace Falco\Iterator;

class Filter extends \FilterIterator
{
    private $iter;
    private $fn;
    private $ok;

    public function __construct($fn, $iter, $ok = true)
	{
        parent::__construct($iter);
        $this->iter = $iter;
        $this->fn = $fn;
        $this->ok = $ok;
    }

    public function accept()
	{
        $x = $this->iter->current();
        return call_user_func($this->fn, $x) === $this->ok;
    }
}
