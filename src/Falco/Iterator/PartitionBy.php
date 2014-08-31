<?php
#namespace Falco\Iterator;

class PartitionBy extends \IteratorIterator
{
    private $fn;
    private $xs;
    private $x;
    private $val;

    public function __construct($fn, $iter)
    {
        $this->fn   = $fn;
        $this->iter = $iter;
        parent::__construct($iter);
    }

    public function rewind()
    {
        $this->iter->rewind();
        $this->x   = $this->iter->current();
        $this->val = call_user_func($this->fn, $this->x);
        $this->next();
    }

    public function current()
    {
        return $this->xs;
    }

    public function valid()
    {
        return $this->iter->valid();
    }

    public function next()
    {
        $iter = $this->iter;
        $fn   = $this->fn;
        $val  = $this->val;
        $xs   = array($this->x);

        while (true) {
            $iter->next();
            $x = $iter->current();
            if (! $iter->valid()) {
                break;
            }
            $prev  = $val;
            $val   = call_user_func($fn, $x);

            if ($val !== $prev) {
                $this->x = $x;
                break;
            }
            $xs[] = $x;
        }
        $this->val = $val;
        $this->xs  = $xs;
    }
}
