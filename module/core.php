<?php
namespace Falco\module\core;

use Falco\F as F;

/**
 * An internal implementation detail, not to be used directly.
 *
 * The use of "thread" here refers to Clojure's [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E) and not to a thread of execution.
 */
final class FThread {
	private $needle;
	public function __construct($needle) {
		$this->needle = $needle;
	}
	public function __call($method, $args) {
		// A pseudo-method that returns the transformed needle and ends the thread.
		if ($method === 'value') {
			return $this->needle;
		} else {
			// The needle can be positioned anywhere in the arg list by injecting
			// it with the placeholder constant.
			$injected = false;
			foreach ($args as $i => $arg) {
				if ($arg === F::_) {
					$args[$i] = $this->needle;
					$injected = true;
					break;
				}
			}
			// By default, the needle will be the last arg passed to the method.
			if (! $injected) {
				$args[] = $this->needle;
			}
			$this->needle = call_user_func_array("Falco\F::$method", $args);
			return $this;
		}
	}
}

/**
 * ### thread
 * Absent macros that facilitate the elegance of clojure's [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E), instead, this emulates Underscore's [chain](http://underscorejs.org/#chain).
 */
$thread = function ($needle) {
	return new FThread($needle);
};

/**
 * ### partial
 * Takes a function f and fewer than the normal arguments to f, and
 * returns a fn that takes a variable number of additional args. When
 * called, the returned function calls f with args + additional args.
 */
$partial = function () {
	$args = func_get_args();
	$f    = array_shift($args);
	return function () use ($f, $args) {
		$rest = func_get_args();
		foreach ($args as $i => $a) {
			if ($a === F::_) {
				$args[$i] = array_shift($rest);
			}
		}
		return call_user_func_array($f, array_merge($args, $rest));
	};
};

/**
 * ### always
 * Create a fn that always returns $x.
 *
 * @example A fn that always returns true.
 *   F::always(true);
 */
$always = $constantly = function ($x) {
	return function () use ($x) {
		$args = func_get_args();
		return $x;
	};
};

$identity = function ($x) { return $x; };
$isOdd    = function ($x) { return abs($x) % 2 === 1; };
$isEven   = function ($x) { return abs($x) % 2 === 0; };
$isTruthy = function ($x) { return !! $x; };
$isFalsy  = function ($x) { return ! $x; };
$isFalsey = $isFalsy; // For those that disagree with or can't remember how to spell it.
$isEmpty  = function ($x) { return empty($x); };
$isPositive = function ($x) { return $x > 0; };
$isNegative = function ($x) { return $x < 0; };
$isZero     = function ($x) { return $x === 0; };

// ### min
$min = 'min'; // no special magic needed here.
/// ### max
$max = 'max';

/**
 * Creates a math fn with a known value:
 * `$doubleInc = F::compose(F::addBy(1), F::multiplyBy(2));
 * $doubleInc(50);
 * // => 101
 * $doubleInc(10);
 * // => 21`
 */
// ### addBy
$addBy      = F::curry(function ($n, $x) { return $x + $n; }, 2);
// ### subtractBy
$subtractBy = F::curry(function ($n, $x) { return $x - $n; }, 2);
// ### multiplyBy
$multiplyBy = F::curry(function ($n, $x) { return $x * $n; }, 2);
// ### divideBy
$divideBy   = F::curry(function ($n, $x) { return $x / $n; }, 2);

// ### square
$square = function ($x) { return $x * $x; };

/**
 * ### sum
 * `F::sum(1,2,3);
 * => 6
 * F::sum([1,2,3]);
 * => 6`
 */
$sum = function () {
	$args = func_get_args();
	if (is_array($args[0])) {
		return array_sum($args[0]);
	}
	return array_sum($args);
};
/**
 * ### product
 * `F::product(1,2,3);
 * => 6
 * F::product([1,2,3]);
 * => 6`
 */
$product = function () {
	$args = func_get_args();
	if (is_array($args[0])) {
		return array_product($args[0]);
	}
	return array_product($args);
};
/**
 * ### all
 * `F::all(F::isOdd(), [1,2,3,4]);
 * => false
 * F::all(F::isOdd(), [1,3,5]);
 * => true`
 */
$all = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (! call_user_func($f, $x)) return false;
	return true;
}, 2);
/**
 * ### any
 * `F::any(F::isOdd(), [1,2,3,4]);
 * => true
 * F::any(F::isOdd(), [2,4,6]);
 * => false`
 */
$any = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return true;
	return false;
}, 2);
/**
 * ### none
 * `F::none(F::isOdd(), [1,2,3,4]);
 * => false
 * F::none(F::isOdd(), [2,4,6]);
 * => true`
 */
$none = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return false;
	return true;
}, 2);

// ### eq
$eq = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] === $args[1];
		case 3: return ($args[0] === $args[1]) && ($args[1] === $args[2]);
		case 1: return true;
	}
	foreach ($args as $i => $v) {
		if (! isset($args[$i + 1])) break;
		$next = $args[$i + 1];
		if ($v !== $next) {
			return false;
		}
	}
	return true;
}, 2);

// ### lt
$lt = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] < $args[1];
		case 3: return ($args[0] < $args[1]) && ($args[1] < $args[2]);
		case 1: return true;
	}
	foreach ($args as $i => $v) {
		if (! isset($args[$i + 1])) break;
		$next = $args[$i + 1];
		if ($v >= $next) {
			return false;
		}
	}
	return true;
}, 2);
// ### lte
$lte = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] <= $args[1];
		case 3: return ($args[0] <= $args[1]) && ($args[1] <= $args[2]);
		case 1: return true;
	}
	foreach ($args as $i => $v) {
		if (! isset($args[$i + 1])) break;
		$next = $args[$i + 1];
		if ($v > $next) {
			return false;
		}
	}
	return true;
}, 2);

// ### gt
$gt = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] > $args[1];
		case 3: return ($args[0] > $args[1]) && ($args[1] > $args[2]);
		case 1: return true;
	}
	foreach ($args as $i => $v) {
		if (! isset($args[$i + 1])) break;
		$next = $args[$i + 1];
		if ($v <= $next) {
			return false;
		}
	}
	return true;
}, 2);
// ### gte
$gte = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] >= $args[1];
		case 3: return ($args[0] >= $args[1]) && ($args[1] >= $args[2]);
		case 1: return true;
	}
	foreach ($args as $i => $v) {
		if (! isset($args[$i + 1])) break;
		$next = $args[$i + 1];
		if ($v < $next) {
			return false;
		}
	}
	return true;
}, 2);

// ### opNot, not
$opNot = $not = function ($f) {
	if (is_callable($f)) {
		return function ($x) use ($f) {
			return ! call_user_func($f, $x);
		};
	} else {
		return function ($x) use ($f) {
			return ! ($f === $x);
		};
	}
};

// ### opAnd
$opAnd = function () {
	$fns = func_get_args();
	switch (count($fns)) {
		case 1: list($f) = $fns;
			return function () use ($f) {
				$args = func_get_args();
				return call_user_func_array($f, $args);
			};
		case 2: list($f, $g) = $fns;
			return function () use ($f, $g) {
				$args = func_get_args();
				return call_user_func_array($f, $args) &&
						call_user_func_array($g, $args);
			};
		case 3: list($f, $g, $h) = $fns;
			return function () use ($f, $g, $h) {
				$args = func_get_args();
				return call_user_func_array($f, $args) &&
						call_user_func_array($g, $args) &&
						call_user_func_array($h, $args);
			};
	}
	return function () use ($fns) {
		$args = func_get_args();
		return F::all(function ($f) use ($args) {
			return call_user_func_array($f, $args);
		}, $fns);
	};
};
// ### opOr
$opOr = function () {
	$fns = func_get_args();
	switch (count($fns)) {
		case 1: list($f) = $fns;
			return function () use ($f) {
				$args = func_get_args();
				return call_user_func_array($f, $args);
			};
		case 2: list($f, $g) = $fns;
			return function () use ($f, $g) {
				$args = func_get_args();
				return call_user_func_array($f, $args) ||
						call_user_func_array($g, $args);
			};
		case 3: list($f, $g, $h) = $fns;
			return function () use ($f, $g, $h) {
				$args = func_get_args();
				return call_user_func_array($f, $args) ||
						call_user_func_array($g, $args) ||
						call_user_func_array($h, $args);
			};
	}
	return function () use ($fns) {
		$args = func_get_args();
		return ! F::none(function ($f) use ($args) {
			return call_user_func_array($f, $args);
		}, $fns);
	};
};

// ### count
$count = function ($xs) {
	if (is_array($xs))  return count($xs);
	if (is_string($xs)) return strlen($xs);
	if (is_object($xs)) {
		if ($xs instanceof Countable) {
			return count($xs);
		}
		return count(get_object_vars($xs));
	}
};
// ### countBy
$countBy = F::curry(function ($f, $xs) {
	if (is_string($xs)) return str_split($xs);
	$cnt = 0;
	foreach ($xs as $x) {
		$cnt += call_user_func($f, $x);
	}
	return $cnt;
}, 2);
// ### values
$values = function ($xs) {
	if (is_array($xs))  return array_values($xs);
	if (is_object($xs)) return array_values(get_object_vars($xs));
	if (is_string($xs)) return str_split($xs);
};
// ### keys
$keys = function ($xs) {
	if (is_array($xs))  return array_keys($xs);
	if (is_object($xs)) return array_keys(get_object_vars($xs));
	if (is_string($xs)) return range(0, strlen($xs));
};

// ### toPairs
$toPairs = function ($xs) {
	if (is_string($xs)) return str_split($xs);
	if (is_array($xs) || (is_object($xs) && $xs instanceof Traversable)) {
		$out = array();
		while ($pair = each($xs)) {
			$out[] = $pair;
		}
		return $out;
	}
};
// ### fromPairs
$fromPairs = function ($xs) {
	reset($xs);
	$out = array();
	while (list($key, $val) = $xs) {
		$out[$key] = $val;
	}
	return $out;
};

// ### sort
$sort    = 'sort';
$ksort   = 'ksort';
$asort   = 'asort';
$sortBy  = F::curry(function ($cmp, $in) { usort($in, $cmp);  return $in; }, 2);
$ksortBy = F::curry(function ($cmp, $in) { uksort($in, $cmp); return $in; }, 2);
$asortBy = F::curry(function ($cmp, $in) { uasort($in, $cmp); return $in; }, 2);

// ### reverse
$reverse = function ($in) {
	switch (gettype($in)) {
		case 'array':  $arr = $in; break;
		case 'string': $arr = str_split($in); break;
		case 'object': $arr = get_object_vars($in); break;
		default: return $in;
	}
	return array_reverse($arr);
};

// ### contains
$contains = F::curry(function ($needle, $haystack) {
	switch (gettype($haystack)) {
		case 'array':  return in_array($needle, $haystack);
		case 'string': return strpos($haystack, $needle) !== false;
		case 'object':
			$vars = get_object_vars($haystack);
			return in_array($needle, $vars);
	}
	return false;
}, 2);
// ### containsBy
$containsBy = F::curry(function ($f, $xs) {
	if (is_string($xs)) return str_split($xs);
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			return true;
		}
	}
	return false;
}, 2);

// ### juxt
$juxt = function () {
	$fns = func_get_args();
	return function ($x) {
		$out = array();
		foreach ($fns as $f) {
			$out[] = call_user_func($f, $x);
		}
		return $out;
	};
};

// ### call
$call  = 'call_user_func';
// ### apply
$apply = 'call_user_func_array';

// ### compose
$compose = function () {
	$fns = func_get_args();
	$c = 'call_user_func';
	switch (count($fns)) {
		case 1: list($f) = $fns;
		return function ($x) use ($c, $f) {
			return $c($f, $x);
		};
		case 2: list($f, $g) = $fns;
		return function ($x) use ($c, $f, $g) {
			return $c($f, $c($g, $x));
		};
		case 3: list($f, $g, $h) = $fns;
		return function ($x) use ($c, $f, $g, $h) {
			return $c($f, $c($g, $c($h, $x)));
		};
		case 4: list($f, $g, $h, $i) = $fns;
		return function ($x) use ($c, $f, $g, $h, $i) {
			return $c($f, $c($g, $c($h, $c($i, $x))));
		};
		case 5: list($f, $g, $h, $i, $j) = $fns;
		return function ($x) use ($c, $f, $g, $h, $i, $j) {
			return $c($f, $c($g, $c($h, $c($i, $c($j, $x)))));
		};
	}
	return function ($x) use ($fns) {
		for ($i = count($fns) - 1;  $i >= 0; $i--) {
			$x = call_user_func($fns[$i], $x);
		}
		return $x;
	};
};
// ### piep
$pipe = function () {
	$fns = func_get_args();
	$fns = array_reverse($fns);
	return call_user_func_array(F::compose(), $fns);
};

// ### useOver
$useOver = function ($used, $over) {
	return F::curry(function ($overArg, $usedArg) use ($used, $over) {
		$overNow = call_user_func($over, $overArg);
		return call_user_func($used, $overNow, $usedArg);
	}, 2);
};
// ### useUnder
$useUnder = function ($used, $under) {
	return F::curry(function ($usedArg, $underArg) use ($used, $under) {
		$underNow = call_user_func($under, $underArg);
		return call_user_func($used, $underNow, $usedArg);
	}, 2);
};

// ### useWith
$useWith = function () {
	$transformers = func_get_args();
	$fn = array_shift($transformers);
	return F::curry(function () use ($fn, $transformers) {
		$args = func_get_args();
		foreach ($transformers as $i => $trans) {
			$args[$i] = call_user_func($trans, $args[$i]);
		}
		return call_user_func_array($fn, $args);
	}, count($transformers));
};

// ### once
$once = function ($fn) {
	$called = false;
	$result = null;
	return function () use ($fn, & $called, & $result) {
		if ($called) {
			return $result;
		}
		$called = true;
		$result = call_user_func_array($fn, func_get_args());
		return $result;
	};
};

// ### prop
$prop = $nth = F::curry(function ($name, $el) {
	return isset($el[$name]) ? $el[$name] : null;
}, 2);
// ### propEq
$propEq = F::curry(function ($key, $val, $arr) {
	return isset($arr[$key]) ? $arr[$key] === $val : false;
}, 3);

// ### pick
$pick = function ($names) {
	$names = array_flip($names);
	return function ($el) use ($names) {
		return array_intersect_key($el, $names);
	};
};
// ### omit
$omit = function ($names) {
	$names = array_flip($names);
	return function ($el) use ($names) {
		return array_diff_key($el, $names);
	};
};
// ### project
$project = F::curry(function ($names, $data) {
	return F::map(F::pick($names), $data);
}, 2);

// ### range
$range = function ($from, $to, $step = 1) {
	return range($from, $to, $step);
};

// ### where
$where = function ($kvs, $strict = true) {
	if ($strict) {
		return function ($el) use ($kvs) {
			if (is_array($el)) {
				foreach ($kvs as $k => $v) {
					if (is_callable($v)) {
						if (! call_user_func($v, $el[$k])) {
							return false;
						}
					}
					else if ($el[$k] !== $v) {
						return false;
					}
				}
			} else {
				foreach ($kvs as $k => $v) {
					if (is_callable($v)) {
						if (! call_user_func($v, $el->$k)) {
							return false;
						}
					}
					else if ($el->$k !== $v) {
						return false;
					}
				}
			}
			return true;
		};
	} else {
		return function ($el) use ($kvs) {
			if (is_array($el)) {
				foreach ($kvs as $k => $v) {
					if (is_callable($v)) {
						if (! call_user_func($v, $el[$k])) {
							return false;
						}
					}
					else if ($el[$k] != $v) {
						return false;
					}
				}
			} else {
				foreach ($kvs as $k => $v) {
					if (is_callable($v)) {
						if (! call_user_func($v, $el->$k)) {
							return false;
						}
					}
					else if ($el->$k != $v) {
						return false;
					}
				}
			}
			return true;
		};
	}
};

// ### take
$take = F::curry(function ($n, $xs) {
	switch (gettype($xs)) {
		case 'array':  return array_slice($xs, 0, $n);
		case 'string': return substr($xs, 0, $n);
		case 'object':
			if ($xs instanceof \Traversable) {
				reset($xs);
				$cnt = 0;
				$out = array();
				while ($cnt < $n && list($key, $val) = each($xs)) {
					$cnt++;
					$out[$key] = $val;
				}
				return $out;
			}
	}
}, 2);

// ### first
$first  = $head = $take(1);
// ### ffirst
$ffirst = $compose($first, $first);

// ### last
$last = function ($xs) {
	if (is_string($xs)) {
		return $xs[strlen($xs) - 1];
	}
	return $xs[count($xs) - 1];
};

// ### skip
$skip = F::curry(function ($n, $xs) {
	switch (gettype($xs)) {
		case 'array':  return array_slice($xs, $n);
		case 'string': return substr($xs, $n);
		case 'object':
			if ($xs instanceof \Traversable) {
				reset($xs);
				for ($i = 0; $i < $n; $i++) {
					next($xs);
				}
				$out = array();
				while (list($key, $val) = each($xs)) {
					$out[$key] = $val;
				}
				return $out;
			}
	}
}, 2);

// ### rest
$rest  = $tail = $skip(1);
// ### frest
$frest = $compose($first, $rest);

// ### takeWhile
$takeWhile = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$out = array();
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			break;
		}
		$out[] = $x;
	}
	return $out;
}, 2);

// ### takeUntil
$takeUntil = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$out = array();
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			$out[] = $x;
			break;
		}
		$out[] = $x;
	}
	return $out;
}, 2);

// ### skipWhile
$skipWhile = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$i = 0;
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			$i++;
		}
	}
	return array_slice($xs, $i);
}, 2);

// ### skipUntil
$skipUntil = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$i = 0;
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			$i++;
		}
	}
	return array_slice($xs, $i);
}, 2);

// ### repeat
$repeat = F::curry(function ($el, $times) {
	if ($times === 0) {
		return array();
	}
	return array_fill(0, $times, $el);
}, 2);

// ### zipmap
$zipmap = F::curry('array_combine', 2);

// ### concat
$concat = function () {
	$args = func_get_args();
	if (count($args) === 0) {
		return array();
	}
	$first = $args[0];
	if (is_array($first)) {
		return call_user_func_array('array_merge', $args);
	} else if (is_string($first)) {
		return implode('', $args);
	}
	return $args;
};

// ### map
$map = F::curry(function () {
	$args = func_get_args();
	$f    = array_shift($args);
	$out  = array();
	switch (count($args)) {
		case 1:
			list($xs) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			foreach ($xs as $k => $x) {
				$out[$k] = call_user_func($f, $x);
			}
			break;
		case 2:
			list($xs, $ys) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			if (is_string($ys)) $ys = str_split($ys);
			reset($ys);
			foreach ($xs as $x) {
				if ($y = each($ys)) {
					$out[] = call_user_func($f, $x, $y[1]);
				} else {
					break;
				}
			}
			break;
		case 3:
			list($xs, $ys, $zs) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			if (is_string($ys)) $ys = str_split($ys);
			if (is_string($zs)) $zs = str_split($zs);
			reset($ys); reset($zs);
			foreach ($xs as $x) {
				if (list($yk, $yv) = each($ys)) {
					if (list($zk, $zv) = each($zs)) {
						$out[] = call_user_func($f, $x, $yv, $zv);
					} else {
						break;
					}
				} else {
					break;
				}
			}
			break;
		default:
			$numArgs = count($args);
			$xs = array_shift($args);
			if (is_string($xs)) $xs = str_split($xs);
			foreach ($args as $i => $arg) {
				if (is_string($arg)) $args[$i] = str_split($arg);
				else reset($arg);
			}
			foreach ($xs as $x) {
				$vals = array($x);
				for ($i = 0; $i < $numArgs - 1; $i++) {
					if (list($k, $v) = each($args[$i])) {
						$vals[] = $v;
					}
				}
				if (count($vals) !== $numArgs) {
					break;
				}
				$out[] = call_user_func_array($f, $vals);
			}
			break;
	}
	return $out;
}, 2);

// ### mapkv
$mapkv = F::curry(function () {
	$args = func_get_args();
	$f    = array_shift($args);
	$out  = array();
	switch (count($args)) {
		case 1:
			list($xs) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			while ($x = each($xs)) {
				$out[$x['key']] = call_user_func($f, $x);
			}
			break;
		case 2:
			list($xs, $ys) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			if (is_string($ys)) $ys = str_split($ys);
			reset($ys);
			while ($x = each($xs)) {
				if ($y = each($ys)) {
					$out[] = call_user_func($f, $x, $y);
				} else {
					break;
				}
			}
			break;
		case 3:
			list($xs, $ys, $zs) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			if (is_string($ys)) $ys = str_split($ys);
			if (is_string($zs)) $zs = str_split($zs);
			reset($ys); reset($zs);
			while ($x = each($xs)) {
				if ($y = each($ys)) {
					if ($z = each($zs)) {
						$out[] = call_user_func($f, $x, $y, $z);
					} else {
						break;
					}
				} else {
					break;
				}
			}
			break;
		default:
			$numArgs = count($args);
			$xs = array_shift($args);
			if (is_string($xs)) $xs = str_split($xs);
			foreach ($args as $i => $arg) {
				if (is_string($arg)) $args[$i] = str_split($arg);
				reset($args[$i]);
			}
			reset($xs);
			while ($x = each($xs)) {
				$kvals = array($x);
				for ($i = 0; $i < $numArgs - 1; $i++) {
					if ($kv = each($args[$i])) {
						$kvals[] = $kv;
					}
				}
				if (count($kvals) !== $numArgs) {
					break;
				}
				$out[] = call_user_func_array($f, $kvals);
			}
			break;
	}
	return $out;
}, 2);

// ### mapcat
$mapcat = F::curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::map(), $fn_with_args));
}, 2);
// ### mapcatkv
$mapcatkv = F::curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::mapkv(), $fn_with_args));
}, 2);

// ### filter
$filter = F::curry(function ($f, $xs) {
	$out = array();
	if (is_string($xs)) $xs = str_split($xs);
	if (is_array($xs) || $xs instanceOf Traversable) {
		foreach ($xs as $x) {
			if (call_user_func($f, $x)) {
				$out[] = $x;
			}
		}
	}
	return $out;
}, 2);
// ### filterkv
$filterkv = F::curry(function ($f, $xs) {
	$out = array();
	if (is_string($xs)) $xs = str_split($xs);
	if (is_array($xs) || $xs instanceOf Traversable) {
		while ($x = each($xs)) {
			if (call_user_func($f, $x)) {
				$out[$x[0]] = $x[1];
			}
		}
	}
	return $out;
}, 2);

// ### ffilter
$ffilter   = $compose($first, $filter);
// ### ffilterkv
$ffilterkv = $compose($first, $filterkv);

// ### remove
$remove = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$out = array();
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			$out[] = $x;
		}
	}
	return $out;
}, 2);
// ### removekv
$removekv = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$out = array();
	while ($x = each($xs)) {
		if (! call_user_func($f, $x)) {
			$out[$x[0]] = $x[1];
		}
	}
	return $out;
}, 2);

// ### reduce, foldl
$reduce = $foldl = F::curry(function ($f, $initialValue, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	return array_reduce($xs, $f, $initialValue);
}, 2);
// ### reducekv, foldkv
$reducekv = $foldlkv = F::curry(function ($f, $initialValue, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$accumulator = $initialValue;
	while ($x = each($xs)) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);
// ### reduceRight, foldr
$reduceRight = $foldr = F::curry(function ($f, $initialValue, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$xs = array_reverse($xs);
	return array_reduce($xs, $f, $initialValue);
}, 2);
// ### foldrkv
$foldrkv = F::curry(function ($f, $initialValue, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$accumulator = $initialValue;
	$xs = array_reverse($xs);
	while ($x = each($xs)) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);

$frequencies = 'array_count_values';

// ### partitionBy
$partitionBy = F::curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$out = array();
	$flag = null;
	$group = array();
	foreach ($xs as $x) {
		$truthy = call_user_func($f, $x);
		if ($truthy !== $flag) {
			$flag = $truthy;
			if (count($group)) {
				$out[] = $group;
			}
			$group = array();
		}
		$group[] = $x;
	}
	if (count($group)) {
		$out[] = $group;
	}
	return $out;
}, 2);

/**
 * ### indexBy
 * Creates a reverse index of your data, similar to
 * [array_column()](http://php.net/array_column), but far more powerful.
 *
 * Specifically, it is a variation of groupBy that presumes that only ONE
 * row can match the $key, so that it can provide a mapping of:
 *
 *     array(
 *       $row1[$key] => $row1,
 *       $row2[$key] => $row2,
 *     );
 *
 * @param mixed $mixedKeys
 *
 *  When multiple keys are provided, it will create a nested array, applying the
 *  keys from left to right to create the index for each level.
 *
 *  - single column (string/col-index)
 *  - array of columns, optionally mapped to transform fns:
 *
 *     ['id', 'name']
 *     ['id' => F::multiplyBy(-1), 'name']
 *
 *  - a callback that is given the entire row and returns the new value.
 *
 *     F::compose('strtoupper', F::prop('name'));
 *
 * @param mixed $mixedVals
 *
 *  - null (leave alone)
 *  - single column (string/col-index)
 *  - array of columns, optionally mapped to transform fns
 *  - callback
 *
 * @param array $in An array of arrays or objects implementing ArrayAccess.
 *
 * @return array
 *
 * @example 1
 *
 * $in = [['id' => 3, 'name' => 'alex'],
 *        ['id' => 5, 'name' => 'john']];
 *
 * indexBy('id', null, $in);
 * => [3 => ['id' => 3, 'name' => 'alex'],
 *     5 => ['id' => 5, 'name' => 'john']]
 *
 * indexBy('id', 'name', $in);
 * => [3 => 'alex',
 *     5 => 'john']
 *
 * $upperName = F::compose('strtoupper', F::prop('name'));
 *
 * indexBy(['id' => F::multiplyBy(-1)], $upperName, $in);
 * => [-3 => 'ALEX',
 *     -5 => 'JOHN']
 *
 * indexBy(['name', 'id'], null, $in)
 * => ['alex' => [3 => ['id' => 3, 'name' => 'alex']],
 *     'john' => [5 => ['id' => 5, 'name' => 'john']]]
 */
$indexBy = F::curry(function ($mixedKeys, $mixedVals, $in) {
	$out = array();

	if (is_array($mixedKeys)) {
		$last_idx = count($mixedKeys) - 1;
		foreach ($in as $n) {

			$ref =& $out;
			$i = 0;
			foreach ($mixedKeys as $keyKey => $keyFn) {

				$kval = null;
				if (is_callable($keyFn)) {
					if (isset($n[$keyKey])) {
						$kval = call_user_func($keyFn, $n[$keyKey]);
					}
				} else if (isset($n[$keyFn])) {
					$kval = $n[$keyFn];
				}
				if ($kval === null) {
					break;
				}

				if (! isset($ref[$kval])) {
					$ref[$kval] = array();
				}

				if ($i === $last_idx) {
					if (is_null($mixedVals)) {
						$ref[$kval] = $n;
					} else if (is_array($mixedVals)) {
						$val = array();
						foreach ($mixedVals as $valKey => $valFn) {
							if (is_callable($valFn)) {
								if (isset($n[$valKey])) {
									$val[$valKey] = call_user_func($valFn, $n[$valKey]);
								}
							} else if (isset($n[$valFn])) {
								$valKey = $valFn;
								$val[$valKey] = $n[$valKey];
							}
						}
						$ref[$kval] = $val;
					} else if (is_callable($mixedVals)) {
						$ref[$kval] = call_user_func($mixedVals, $n);
					} else if (isset($n[$mixedVals])) {
						$ref[$kval] = $n[$mixedVals];
					}
				} else {
					$ref =& $ref[$kval];
				}
				$i++;
			}
		}
	}
	else if (! is_object($mixedKeys) && strlen($mixedKeys) !== 0) {
		$key = $mixedKeys;
		if (is_null($mixedVals)) {
			foreach ($in as $n) {
				if (isset($n[$key])) {
					$out[$n[$key]] = $n;
				}
			}
		} else if (is_array($mixedVals)) {
			foreach ($in as $n) {
				if (! isset($n[$key])) {
					continue;
				}
				$val = array();
				foreach ($mixedVals as $valKey => $valFn) {
					if (is_callable($valFn)) {
						if (isset($n[$valKey])) {
							$val[$valKey] = call_user_func($valFn, $n[$valKey]);
						}
					} else if (isset($n[$valFn])) {
						$valKey = $valFn;
						$val[$valKey] = $n[$valKey];
					}
				}
				$out[$n[$key]] = $val;
			}
		} else if (is_callable($mixedVals)) {
			foreach ($in as $n) {
				$out[$n[$key]] = call_user_func($mixedVals, $n);
			}
		} else {
			foreach ($in as $n) {
				if (isset($n[$key])) {
					$out[$n[$key]] = isset($n[$mixedVals]) ? $n[$mixedVals] : null;
				}
			}
		}
	}
	return $out;
}, 3);

/**
 * ### groupBy
 * Creates a view of your data indexed by a grouping strategy.
 *
 * Specifically, it is a variation of indexBy that allows for more than one row
 * to match the key, so that it can provide a mapping of:
 * array(
 *    $row1[$key] => [all, rows, that, contain, the, same, value, for, $key],
 *    $row2[$key] => [other, rows, matching, a, different, value, for, $key],
 * );
 *
 * @param mixed $mixedKeys
 *
 *  - single column (string/col-index)
 *  - array of columns, optionally mapped to transform fns:
 *   ['id', 'name']
 *   ['id' => F::multilyBy(-1), 'name']
 *
 * @param mixed $mixedVals
 *
 *  - null (leave alone)
 *  - single column (string/col-index)
 *  - array of columns, optionally mapped to transform fns
 *  - callback
 *
 * @param array $in An array of arrays or objects implementing ArrayAccess.
 *
 * @return array
 *
 * @example 1
 *
 * $in = [['id' => 3, 'name' => 'alex'],
 *        ['id' => 5, 'name' => 'john']];
 *
 * groupBy('id', null, $in);
 * => [3 => [['id' => 3, 'name' => 'alex']],
 *     5 => [['id' => 5, 'name' => 'john']]]
 *
 * groupBy('id', 'name', $in);
 * => [3 => [['name' => 'alex']],
 *     5 => [['name' => 'john']]]
 *
 * groupBy(['name', 'id'], null, $in)
 * => ['alex' => [3 => [['id' => 3, 'name' => 'alex']]],
 *     'john' => [5 => [['id' => 5, 'name' => 'john']]]]
 */
$groupBy = F::curry(function ($mixedKeys, $mixedVals, $in) {
	$out = array();

	if (is_array($mixedKeys)) {
		$last_idx = count($mixedKeys) - 1;
		foreach ($in as $n) {

			$ref =& $out;
			$i = 0;
			foreach ($mixedKeys as $keyKey => $keyFn) {

				$kval = null;
				if (is_callable($keyFn)) {
					if (isset($n[$keyKey])) {
						$kval = call_user_func($keyFn, $n[$keyKey]);
					}
				} else if (isset($n[$keyFn])) {
					$kval = $n[$keyFn];
				}
				if ($kval === null) {
					break;
				}

				if (! isset($ref[$kval])) {
					$ref[$kval] = array();
				}

				if ($i === $last_idx) {
					if (is_null($mixedVals)) {
						$ref[$kval][] = $n;
					} else if (is_array($mixedVals)) {
						$val = array();
						foreach ($mixedVals as $valKey => $valFn) {
							if (is_callable($valFn)) {
								if (isset($n[$valKey])) {
									$val[$valKey] = call_user_func($valFn, $n[$valKey]);
								}
							} else if (isset($n[$valFn])) {
								$valKey = $valFn;
								$val[$valKey] = $n[$valKey];
							}
						}
						$ref[$kval][] = $val;
					} else if (is_callable($mixedVals)) {
						$ref[$kval][] = call_user_func($mixedVals, $n);
					} else if (isset($n[$mixedVals])) {
						$ref[$kval][] = $n[$mixedVals];
					}
				} else {
					$ref =& $ref[$kval];
				}
				$i++;
			}
		}
	}
	else if (! is_object($mixedKeys) && strlen($mixedKeys) !== 0) {
		$key = $mixedKeys;
		if (is_null($mixedVals)) {
			foreach ($in as $n) {
				if (isset($n[$key])) {
					$out[$n[$key]][] = $n;
				}
			}
		} else if (is_array($mixedVals)) {
			foreach ($in as $n) {
				if (! isset($n[$key])) {
					continue;
				}
				$val = array();
				foreach ($mixedVals as $valKey => $valFn) {
					if (is_callable($valFn)) {
						if (isset($n[$valKey])) {
							$val[$valKey] = call_user_func($valFn, $n[$valKey]);
						}
					} else if (isset($n[$valFn])) {
						$valKey = $valFn;
						$val[$valKey] = $n[$valKey];
					}
				}
				$out[$n[$key]][] = $val;
			}
		} else if (is_callable($mixedVals)) {
			foreach ($in as $n) {
				$out[$n[$key]][] = call_user_func($mixedVals, $n);
			}
		} else {
			foreach ($in as $n) {
				if (isset($n[$key])) {
					$out[$n[$key]][] = isset($n[$mixedVals]) ? $n[$mixedVals] : null;
				}
			}
		}
	}
	return $out;
}, 3);

/**
 * ### memoize
 *
 * @param Callable $fn         A fn to be memoized.
 * @param Callable $cacheKeyFn Responsible for computing the memoizer's cache key.
 * @param int|null $limit      An optional maximum size of the internal cache.
 * @return Closure The newly memoized version of $fn.
 */
$memoize = function ($fn, $cacheKeyFn, $limit = null) {
	$cache = array();
	return function () use ($fn, $cacheKeyFn, $limit, & $cache) {
		$args = func_get_args();
		$key  = call_user_func_array($cacheKeyFn, $args);
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		$result = call_user_func_array($fn, $args);
		if ($limit !== null && count($cache) === $limit) {
			array_shift($cache);
		}
		$cache[$key] = $result;
		return $result;
	};
};
