<?php
namespace Falco\Iterator;

class Map extends \MultipleIterator
{
    private $fn;
    private $curr;
    private $pos;

    public function __construct($fn, $args)
    {
        parent::__construct();

        $this->fn = $fn;
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
        return $this->pos;
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
