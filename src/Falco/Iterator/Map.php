<?php
namespace Falco\Iterator;

class Map extends \MultipleIterator
{
    private $fn;
    private $curr;
    private $pos;
    private $num_iters;

    public function __construct($fn, $args)
    {

        $this->fn        = $fn;
        $this->num_iters = count($args);

        $flags = \MultipleIterator::MIT_NEED_ALL;
        if ($this->num_iters === 1) {
            $flags |= \MultipleIterator::MIT_KEYS_NUMERIC;
        } else {
            $flags |= \MultipleIterator::MIT_KEYS_ASSOC;
        }
        parent::__construct($flags);

        foreach ($args as $arg) {
            if (is_object($arg)) {
                $this->attachIterator($arg);
            } else {
                $this->attachIterator(new \ArrayIterator($arg));
            }
        }
    }

    public function rewind()
    {
        $this->pos = 0;
        parent::rewind();
        if (parent::valid()) {
            $this->curr = call_user_func_array($this->fn, parent::current());
        }
    }

    /**
     * Can not use the parent value due it being an array, which PHP does not
     * allow as a key type.
     * - https://wiki.php.net/rfc/foreach-non-scalar-keys?s[]=multipleiterator
     */
    public function key()
    {
        if ($this->num_iters === 1) {
            $key = parent::key();
            return $key[0];
        } else {
            return $this->pos;
        }
    }

    public function current()
    {
        return $this->curr;
    }

    public function next()
    {
        $this->pos++;
        parent::next();
        if (parent::valid()) {
            $this->curr = call_user_func_array($this->fn, parent::current());
        }
    }
}
