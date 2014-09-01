<?php
namespace Falco\Iterator;

class PartitionBy extends \IteratorIterator
{
    private $fn;
    private $xs;
    private $curr;
    private $val;
    private $pos;
    private $oneMore;

    public function __construct($fn, $iter)
    {
        $this->fn   = $fn;
        $this->iter = $iter;
        parent::__construct($iter);
    }

    public function rewind()
    {
        $this->oneMore = false;
        $this->iter->rewind();
        if ($this->iter->valid()) {
            $this->curr = $this->iter->current();
            $this->val  = call_user_func($this->fn, $this->curr);
            $this->pos  = -1;
            $this->next();
        }
    }

    public function key()
    {
        return $this->pos;
    }

    public function current()
    {
        return $this->xs;
    }

    public function valid()
    {
        return $this->iter->valid() || $this->oneMore;
    }

    public function next()
    {
        $this->pos++;

        $iter = $this->iter;
        $fn   = $this->fn;
        $val  = $this->val;
        $xs   = array($this->curr);

        while (true) {
            $iter->next();
            if (! $iter->valid()) {
                $this->oneMore = ! $this->oneMore;
                break;
            }
            $x     = $iter->current();
            $prev  = $val;
            $val   = call_user_func($fn, $x);

            if ($val !== $prev) {
                $this->curr = $x;
                break;
            }
            $xs[] = $x;
        }
        $this->val = $val;
        $this->xs  = $xs;
    }
}
