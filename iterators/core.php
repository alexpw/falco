<?php namespace Falco\iterators\core;

class Filter extends \FilterIterator {
	private $iter;
	private $fn;
	private $ok;
	public function __construct(\Iterator $iterator, $fn, $ok = true) {
		parent::__construct($iterator);
		$this->iter = $iterator;
		$this->fn = $fn;
		$this->ok = $ok;
	}
	public function accept() {
		$x = $this->iter->current();
		return call_user_func($this->fn, $x) === $this->ok;
	}
}

class SkipWhile extends \FilterIterator {
	private $iter;
	private $fn;
	private $done;
	public function __construct(\Iterator $iterator, $fn) {
		parent::__construct($iterator);
		$this->iter = $iterator;
		$this->fn   = $fn;
		$this->done = false;
	}
	public function rewind() {
		$this->done = false;
		parent::rewind();
	}
	public function accept() {
		if ($this->done) return true;
		$x = $this->iter->current();
		$this->done = call_user_func($this->fn, $x) === true;
		return $this->done;
	}
}
class SkipUntil extends \FilterIterator {
	private $iter;
	private $fn;
	private $done;
	public function __construct(\Iterator $iterator, $fn) {
		parent::__construct($iterator);
		$this->iter = $iterator;
		$this->fn   = $fn;
		$this->done = false;
	}
	public function rewind() {
		$this->done = false;
		parent::rewind();
	}
	public function accept() {
		if ($this->done) return true;
		$x = $this->iter->current();
		$this->done = call_user_func($this->fn, $x) === true;
		return false;
	}
}

class TakeWhile extends \IteratorIterator {
	private $fn;
	public function __construct($iter, $fn) {
		parent::__construct($iter);
		$this->fn = $fn;
	}
	public function valid() {
		return call_user_func($this->fn, $this->current()) === true;
	}
}
class TakeUntil extends \IteratorIterator {
	private $fn;
	private $done;
	public function __construct($iter, $fn) {
		parent::__construct($iter);
		$this->fn = $fn;
	}
	public function rewind() { $this->done = false; }
	public function valid()  {
		if (! $this->done) {
			$this->done = call_user_func($this->fn, $this->current());
			return true;
		}
		return false;
	}
}

class Range implements \Iterator {
	private $key;
	private $curr;
	private $from;
	private $to;
	private $step;
	public function __construct($from, $to, $step) {
		$this->from = $from;
		$this->to   = $to;
		$this->step = $step;
	}
	public function rewind()  { $this->curr = $this->from; $this->key = 0; }
	public function valid()   { return $this->curr <= $this->to; }
	public function current() { return $this->curr; }
	public function key()     { return $this->key; }
	public function next()    { $this->curr += $this->step; $this->key++; }
}
