<?php
namespace Falco;

/**
 * Falco is a partial Function Application Library emphasizing COmposition
 *
 * Inspired by ports of JS FP libraries to other languages, this one takes
 * influence from Rambda (https://github.com/CrossEye/ramda) and Clojure.
 *
 */
final class F {

	const _ = '\F::_';

	private static $fns = array();

	public static function set_fn($fn_name, $f) {
		self::$fns[$fn_name] = $f;
	}

	public static function __callStatic($method, $args) {
		if (empty($args)) {
			return self::$fns[$method];
		}
		return call_user_func_array(self::$fns[$method], $args);
	}
}



/**
 * The use of "thread" here refers to
 * http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E
 * and not to a thread of execution.
 */
final class FThread {
	private $needle;
	public function __construct($needle) {
		$this->needle = $needle;
	}
	public function __call($method, $args) {
		if ($method === 'value') {
			return $this->needle;
		} else {
			$injected = false;
			foreach ($args as $i => $arg) {
				if ($arg === F::_) {
					$args[$i] = $this->needle;
					$injected = true;
					break;
				}
			}
			if (! $injected) {
				$args[] = $this->needle;
			}
			$this->needle = call_user_func_array("Falco\F::$method", $args);
			return $this;
		}
	}
}

$thread = function ($needle) {
	return new FThread($needle);
};
F::set_fn('thread', $thread);

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

$curry = function ($f, $numArgs = null) {
	if ($numArgs === null) {
		$r = new \ReflectionFunction($f);
		$numArgs = $r->getNumberOfParameters();
	}
	switch ($numArgs) {
		case 1: return function () use ($f) {
			return call_user_func_array($f, func_get_args());
		};
		case 2: return function () use ($f) {
			$args = func_get_args();
			if (count($args) === 1) {
				return function () use ($f, $args) {
					$args = array_merge($args, func_get_args());
					return call_user_func_array($f, $args);
				};
			}
			return call_user_func_array($f, $args);
		};
		case 3: return function () use ($f) {
			$args = func_get_args();
			if (count($args) === 1) {
				return function () use ($args, $f) {
					$args = array_merge($args, func_get_args());
					if (count($args) === 2) {
						return function ($z) use ($args, $f) {
							list($x, $y) = $args;
							return call_user_func($f, $x, $y, $z);
						};
					}
					return call_user_func_array($f, $args);
				};
			} else if (count($args) === 2) {
				return function ($z) use ($args, $f) {
					list($x, $y) = $args;
					return call_user_func($f, $x, $y, $z);
				};
			}
			return call_user_func_array($f, $args);
		};
	}
	$currier = function ($partialArgs) use (& $currier, $f, $numArgs) {
		return function () use (& $currier, $f, $numArgs, $partialArgs) {
			$args = array_merge($partialArgs, func_get_args());
			if (count($args) >= $numArgs) {
				return call_user_func_array($f, array_slice($args, 0, $numArgs));
			}
			return $currier($args);
		};
	};
	return $currier(array());
};

$always = $constantly = function ($x) {
	return function () use ($x) {
		$args = func_get_args();
		return $x;
	};
};
$alwaysTrue  = $always(true);
$alwaysFalse = $always(false);
$alwaysNull  = $always(null);
$alwaysZero  = $always(0);

$identity = function ($x) { return $x; };
$isOdd    = function ($x) { return abs($x) % 2 === 1; };
$isEven   = function ($x) { return abs($x) % 2 === 0; };
$isTruthy = function ($x) { return !! $x; };
$isFalsy  = function ($x) { return ! $x; };
$isFalsey = $isFalsy;
$isEmpty  = function ($x) { return empty($x); };
$isPositive = function ($x) { return $x > 0; };
$isNegative = function ($x) { return $x < 0; };
$isZero     = function ($x) { return $x === 0; };

$min = function () { return call_user_func_array('min', func_get_args()); };
$max = function () { return call_user_func_array('max', func_get_args()); };

$addBy      = $curry(function ($n, $x) { return $x + $n; }, 2);
$subtractBy = $curry(function ($n, $x) { return $x - $n; }, 2);
$multiplyBy = $curry(function ($n, $x) { return $x * $n; }, 2);
$divideBy   = $curry(function ($n, $x) { return $x / $n; }, 2);

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

$all = $curry(function ($f, $xs) {
	foreach ($xs as $x) if (! call_user_func($f, $x)) return false;
	return true;
}, 2);
$any = $curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return true;
	return false;
}, 2);
$none = $curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return false;
	return true;
}, 2);

$opEq = $eq = $curry(function () {
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

$opLt = $lt = $curry(function () {
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
$opLte = $lte = $curry(function () {
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

$opGt = $gt = $curry(function () {
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
$opGte = $gte = $curry(function () {
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
	if (is_string($xs)) return strlen($xs);
	if (is_array($xs))  return count($xs);
	if (is_object($xs)) return count(get_object_vars($xs));
};
$countBy = $curry(function ($f, $xs) {
	if (is_string($xs)) return str_split($xs);
	$cnt = 0;
	foreach ($xs as $x) {
		$cnt += call_user_func($f, $x);
	}
	return $cnt;
}, 2);
$values = function ($xs) {
	if (is_string($xs)) return str_split($xs);
	if (is_array($xs))  return array_values($xs);
	if (is_object($xs)) return array_values(get_object_vars($xs));
};
$keys = function ($xs) {
	if (is_string($xs)) return range(0, strlen($xs));
	if (is_array($xs))  return array_keys($xs);
	if (is_object($xs)) return array_keys(get_object_vars($xs));
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

$sort    = function ($in) { sort($in);  return $in; };
$ksort   = function ($in) { ksort($in); return $in; };
$asort   = function ($in) { asort($in); return $in; };
$sortBy  = $curry(function ($cmp, $in) { usort($in, $cmp);  return $in; }, 2);
$ksortBy = $curry(function ($cmp, $in) { uksort($in, $cmp); return $in; }, 2);
$asortBy = $curry(function ($cmp, $in) { uasort($in, $cmp); return $in; }, 2);

$reverse = function ($in) {
	switch (gettype($in)) {
		case 'array':  $arr = $in; break;
		case 'string': $arr = str_split($in); break;
		case 'object': $arr = get_object_vars($in); break;
		default: return $in;
	}
	return array_reverse($arr);
};

$contains = $curry(function ($needle, $haystack) {
	switch (gettype($haystack)) {
		case 'array':  return in_array($needle, $haystack);
		case 'string': return strpos($haystack, $needle) !== false;
		case 'object':
			$vars = get_object_vars($haystack);
			return in_array($needle, $vars);
	}
	return false;
}, 2);
$containsBy = $curry(function ($f, $xs) {
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

$apply = function ($f, $args) {
	return call_user_func_array($f, $args);
};

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
	return F::apply(F::compose(), $fns);
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

$prop = $curry(function ($name, $el) {
	return isset($el[$name]) ? $el[$name] : null;
}, 2);
$propEq = $curry(function ($key, $val, $arr) {
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
$project = $curry(function ($names, $data) {
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

$nth = $curry(function ($n, $xs) {
	return isset($xs[$n]) ? $xs[$n] : null;
}, 2);

$take = $curry(function ($n, $xs) {
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

$skip = $curry(function ($n, $xs) {
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

$takeWhile = $curry(function ($f, $xs) {
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

$takeUntil = $curry(function ($f, $xs) {
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

$skipWhile = $curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$i = 0;
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			$i++;
		}
	}
	return array_slice($xs, $i);
}, 2);

$skipUntil = $curry(function ($f, $xs) {
	if (is_string($xs)) $xs = str_split($xs);
	$i = 0;
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			$i++;
		}
	}
	return array_slice($xs, $i);
}, 2);

$repeat = $curry(function ($el, $times) {
	$out = array();
	for ($i = 0; $i < $times; $i++) {
		$out[] = $el;
	}
	return $out;
}, 2);

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

$map = $curry(function () {
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

$mapkv = $curry(function () {
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

$mapcat = $curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::map(), $fn_with_args));
}, 2);
$mapcatkv = $curry(function () {
	$fn_with_args = func_get_args();
	return call_user_func(F::concat(), call_user_func_array(F::mapkv(), $fn_with_args));
}, 2);

$filter = $curry(function ($f, $xs) {
	$out = array();
	foreach ($xs as $x) {
		if (call_user_func($f, $x)) {
			$out[] = $x;
		}
	}
	return $out;
}, 2);
$filterkv = $curry(function ($f, $xs) {
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

$remove = $curry(function ($f, $xs) {
	$out = array();
	foreach ($xs as $x) {
		if (! call_user_func($f, $x)) {
			$out[] = $x;
		}
	}
	return $out;
}, 2);
$removekv = $curry(function ($f, $xs) {
	$out = array();
	while ($x = each($xs)) {
		if (! call_user_func($f, $x)) {
			$out[$x[0]] = $x[1];
		}
	}
	return $out;
}, 2);

$reduce = $curry(function ($f, $initialValue, $xs) {
	$accumulator = $initialValue;
	foreach ($xs as $x) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);
$reducekv = $curry(function ($f, $initialValue, $xs) {
	$accumulator = $initialValue;
	while ($x = each($xs)) {
		$accumulator = call_user_func($f, $accumulator, $x);
	}
	return $accumulator;
}, 2);

$partitionBy = $curry(function ($f, $xs) {
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
 * @examples
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
$indexBy = $curry(function ($mixedKeys, $mixedVals, $in) {
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
 * @examples
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
$groupBy = $curry(function ($mixedKeys, $mixedVals, $in) {
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

foreach (get_defined_vars() as $k => $v) {
	if ($v instanceof \Closure) {
		F::set_fn($k, $v);
	}
}
