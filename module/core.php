<?php
namespace Falco\module\core;

use Falco\F as F;

/**
 * The use of "thread" here refers to [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E) and not to a thread of execution.
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

// Absent macros that facilitate the elegance of clojure's [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E), instead, this emulates Underscore's [chain](http://underscorejs.org/#chain).
$thread = function ($needle) {
	return new FThread($needle);
};

/**
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
 * Create a fn that always returns $x. Example: F::always(true);
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

$min = 'min';
$max = 'max';

/**
 * Creates a math fn with a known value. For example:<br />
 * `$doubleInc = F::compose(F::addBy(1), F::multiplyBy(2));
 * $doubleInc(50);
 * // => 101
 * $doubleInc(10);
 * // => 21`
 */
$addBy      = F::curry(function ($n, $x) { return $x + $n; }, 2);
$subtractBy = F::curry(function ($n, $x) { return $x - $n; }, 2);
$multiplyBy = F::curry(function ($n, $x) { return $x * $n; }, 2);
$divideBy   = F::curry(function ($n, $x) { return $x / $n; }, 2);

$square = function ($x) { return $x * $x; };

$sum = function () {
	$args = func_get_args();
	if (is_array($args[0])) {
		return array_sum($args[0]);
	}
	return array_sum($args);
};
$product = function () {
	$args = func_get_args();
	if (is_array($args[0])) {
		return array_product($args[0]);
	}
	return array_product($args);
};

$all = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (! call_user_func($f, $x)) return false;
	return true;
}, 2);
$any = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return true;
	return false;
}, 2);
$none = F::curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return false;
	return true;
}, 2);

$opEq = $eq = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] === $args[1];
		case 3: return ($args[0] === $args[1]) && ($args[1] === $args[2]);
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

$opLt = $lt = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] < $args[1];
		case 3: return ($args[0] < $args[1]) && ($args[1] < $args[2]);
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
$opLte = $lte = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] <= $args[1];
		case 3: return ($args[0] <= $args[1]) && ($args[1] <= $args[2]);
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

$opGt = $gt = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] > $args[1];
		case 3: return ($args[0] > $args[1]) && ($args[1] > $args[2]);
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
$opGte = $gte = F::curry(function () {
	$args = func_get_args();
	switch (count($args)) {
		case 2: return $args[0] >= $args[1];
		case 3: return ($args[0] >= $args[1]) && ($args[1] >= $args[2]);
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

$opNot = function ($f) {
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

$opAnd = function () {
	$fns = func_get_args();
	switch (count($fns)) {
		case 1: list($f) = $fns;
			return function ($x) use ($f) {
				return call_user_func($f, $x);
			};
		case 2: list($f, $g) = $fns;
			return function ($x) use ($f, $g) {
				return call_user_func($f, $x) && call_user_func($g, $x);
			};
		case 3: list($f, $g, $h) = $fns;
			return function ($x) use ($f, $g, $h) {
				return call_user_func($f, $x) &&
					call_user_func($g, $x) &&
					call_user_func($h, $x);
			};
	}
	return function ($x) use ($fns) {
		return $all(function ($f) use ($x) {
			return call_user_func($f, $x);
		}, $fns);
	};
};
$opOr = function () {
	$fns = func_get_args();
	switch (count($fns)) {
		case 1: list($f) = $fns;
			return function ($x) use ($f) {
				return call_user_func($f, $x);
			};
		case 2: list($f, $g) = $fns;
			return function ($x) use ($f, $g) {
				return call_user_func($f, $x) || call_user_func($g, $x);
			};
		case 3: list($f, $g, $h) = $fns;
			return function ($x) use ($f, $g, $h) {
				return call_user_func($f, $x) ||
					call_user_func($g, $x) ||
					call_user_func($h, $x);
			};
	}
	return function ($x) use ($fns) {
		return ! $none(function ($f) use ($x) {
			return call_user_func($f, $x);
		}, $fns);
	};
};

$count = function ($xs) {
	if (is_array($xs))  return count($xs);
	if (is_object($xs)) return count(get_object_vars($xs));
	if (is_string($xs)) return strlen($xs);
};
$countBy = F::curry(function ($f, $xs) {
	if (is_string($xs)) return str_split($xs);
	$cnt = 0;
	foreach ($xs as $x) {
		$cnt += call_user_func($f, $x);
	}
	return $cnt;
}, 2);
$values = function ($xs) {
	if (is_array($xs))  return array_values($xs);
	if (is_object($xs)) return array_values(get_object_vars($xs));
	if (is_string($xs)) return str_split($xs);
};
$keys = function ($xs) {
	if (is_array($xs))  return array_keys($xs);
	if (is_object($xs)) return array_keys(get_object_vars($xs));
	if (is_string($xs)) return range(0, strlen($xs));
};

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
$fromPairs = function ($xs) {
	reset($xs);
	$out = array();
	while (list($key, $val) = $xs) {
		$out[$key] = $val;
	}
	return $out;
};

$sort    = 'sort';
$ksort   = 'ksort';
$asort   = 'asort';
$sortBy  = F::curry(function ($cmp, $in) { usort($in, $cmp);  return $in; }, 2);
$ksortBy = F::curry(function ($cmp, $in) { uksort($in, $cmp); return $in; }, 2);
$asortBy = F::curry(function ($cmp, $in) { uasort($in, $cmp); return $in; }, 2);

$reverse = function ($in) {
	switch (gettype($in)) {
		case 'array':  $arr = $in; break;
		case 'string': $arr = str_split($in); break;
		case 'object': $arr = get_object_vars($in); break;
		default: return $in;
	}
	return array_reverse($arr);
};

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
$containsBy = F::curry(function ($f, $xs) {
	if (is_string($xs)) return str_split($xs);
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			return true;
		}
	}
	return false;
}, 2);

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

$call  = 'call_user_func';
$apply = 'call_user_func_array';

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
$pipe = function () {
	$fns = func_get_args();
	$fns = array_reverse($fns);
	return call_user_func_array(F::compose(), $fns);
};

$useOver = function ($used, $over) {
	return F::curry(function ($overArg, $usedArg) use ($used, $over) {
		$overNow = call_user_func($over, $overArg);
		return call_user_func($used, $overNow, $usedArg);
	}, 2);
};

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

$prop = F::curry(function ($name, $el) {
	return isset($el[$name]) ? $el[$name] : null;
}, 2);
$propEq = F::curry(function ($key, $val, $arr) {
	return isset($arr[$key]) ? $arr[$key] === $val : false;
}, 3);

$pick = function ($names) {
	$names = array_flip($names);
	return function ($el) use ($names) {
		return array_intersect_key($el, $names);
	};
};
$omit = function ($names) {
	$names = array_flip($names);
	return function ($el) use ($names) {
		return array_diff_key($el, $names);
	};
};
$project = F::curry(function ($names, $data) {
	return F::map(F::pick($names), $data);
}, 2);


$range = function ($from, $to, $step = 1) {
	return range($from, $to, $step);
};

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

$nth = F::curry(function ($n, $xs) {
	return isset($xs[$n]) ? $xs[$n] : null;
}, 2);

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

$first  = $head = $take(1);
$ffirst = $compose($first, $first);

$last = function ($xs) {
	if (is_string($xs)) {
		return $xs[strlen($xs) - 1];
	}
	return $xs[count($xs) - 1];
};

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

$rest  = $tail = $skip(1);
$frest = $compose($first, $rest);

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

$repeat = F::curry(function ($el, $times) {
	if ($times === 0) {
		return array();
	}
	return array_fill(0, $times, $el);
}, 2);

$zipmap = F::curry('array_combine', 2);

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
	} else if (is_object($first) && $first instanceof ArrayAccess) {
		// TODO
	}
	return $args;
};

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

$mapcat = F::curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::map(), $fn_with_args));
}, 2);
$mapcatkv = F::curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::mapkv(), $fn_with_args));
}, 2);

$filter = F::curry(function ($f, $xs) {
	$out = array();
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			$out[] = $x;
		}
	}
	return $out;
}, 2);
$filterkv = F::curry(function ($f, $xs) {
	$out = array();
	while ($x = each($xs)) {
		if (call_user_func($f, $x)) {
			$out[$x[0]] = $x[1];
		}
	}
	return $out;
}, 2);

$ffilter   = $compose($filter, $first);
$ffilterkv = $compose($filterkv, $first);

$remove = F::curry(function ($f, $xs) {
	$out = array();
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			$out[] = $x;
		}
	}
	return $out;
}, 2);
$removekv = F::curry(function ($f, $xs) {
	$out = array();
	while ($x = each($xs)) {
		if (! call_user_func($f, $x)) {
			$out[$x[0]] = $x[1];
		}
	}
	return $out;
}, 2);

$reduce = F::curry(function ($f, $initialValue, $xs) {
	$accumulator = $initialValue;
	foreach ($xs as $x) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);
$reducekv = F::curry(function ($f, $initialValue, $xs) {
	$accumulator = $initialValue;
	while ($x = each($xs)) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);

$partitionBy = F::curry(function ($f, $xs) {
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
 * Creates a unique index of your data, similar to array_column(), but far more
 * powerful.
 *
 * Specifically, it is a variation of groupBy that presumes that only ONE
 * row can match the $key, so that it can provide a mapping of:
 * array(
 *   row1[key] => row1,
 *   row2[key] => row2
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
 * indexBy('id', null, $in);
 * => [3 => ['id' => 3, 'name' => 'alex'],
 *     5 => ['id' => 5, 'name' => 'john']]
 *
 * indexBy('id', 'name', $in);
 * => [3 => ['name' => 'alex'],
 *     5 => ['name' => 'john']]
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
 * Creates a view of your data indexed by a grouping strategy.
 *
 * Specifically, it is a variation of indexBy that allows for more than one row
 * to match the key, so that it can provide a mapping of:
 * array(
 *    row1[key] => row1,
 *    row2[key] => row2
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
