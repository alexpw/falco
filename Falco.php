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

	const identity = 'Falco\F::identity';
	const isOdd    = 'Falco\F::isOdd';
	const isEven   = 'Falco\F::isEven';
	const isTruthy = 'Falco\F::isTruthy';
	const isFalsey = 'Falco\F::isFalsey';
	const isEmpty  = 'Falco\F::isEmpty';

	private static $fns = array();
	public static function set_fn($fn_name, $f) {
		self::$fns[$fn_name] = $f;
	}
	public function __get($fn_name) {
		return self::$fns[$fn_name];
	}
	public static function __callStatic($method, $args) {
		return call_user_func_array(self::$fns[$method], $args);
	}
}

$identity    = function ($x) { return $x; };
$alwaysTrue  = function () { return true; };
$alwaysFalse = function () { return false; };
$alwaysNull  = function () { return null; };

$isOdd    = function ($x) { return $x % 2 === 1; };
$isEven   = function ($x) { return $x % 2 === 0; };
$isTruthy = function ($x) { return !! $x; };
$isFalsey = function ($x) { return ! $x; };
$isEmpty  = function ($x) { return empty($x); };

$min = function () { return call_user_func_array('min', func_get_args()); };
$max = function () { return call_user_func_array('max', func_get_args()); };

/**
 * The use of "thread" here refers to
 * http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E
 * and not to a thread of execution, which php does not have.
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
		case 0:
		case 1: return $f;
		case 2: return function () use ($f) {
			$args = func_get_args();
			if (count($args) === 1) {
				list($x) = $args;
				return function ($y) use ($x, $f) {
					return call_user_func($f, $x, $y);
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
	$currier = function ($partialArgs) use (&$currier, $f, $numArgs) {
		return function () use (&$currier, $f, $numArgs, $partialArgs) {
			$args = array_merge($partialArgs, func_get_args());
			if (count($args) >= $numArgs) {
				return call_user_func_array($f, array_slice($args, 0, $numArgs));
			} else {
				return $currier($args);
			}
		};
	};
	return $currier([]);
};

$all = $curry(function ($f, $xs) {
	foreach ($xs as $x) if (! call_user_func($f, $x)) return false;
	return true;
}, 2);
$none = $curry(function ($f, $xs) {
	foreach ($xs as $x) if (call_user_func($f, $x)) return false;
	return true;
}, 2);

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

$values = function ($xs) {
	if (is_string($xs)) return str_split($xs);
	if (is_array($xs)) return array_values($xs);
	if (is_object($xs) && $xs instanceof Traversable) {
		$out = array();
		foreach ($xs as $x) {
			$out[] = $x;
		}
		return $out;
	}
};
$keys = function ($xs) {
	if (is_string($xs)) return range(0, strlen($xs));
	if (is_array($xs)) return array_keys($xs);
	if (is_object($xs) && $xs instanceof Traversable) {
		$out = array();
		foreach ($xs as $k => $x) {
			$out[] = $k;
		}
		return $out;
	}
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
$contains = $curry(function ($needle, $haystack) {
	if (is_string($haystack)) {
		return strpos($haystack, $needle) !== false;
	}
	if (is_array($haystack)) {
		return in_array($needle, $haystack);
	}
	if (is_object($haystack)) {
		$vars = get_object_vars($haystack);
		return in_array($needle, $vars);
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

$apply = $curry(function ($f, $args) {
	return call_user_func_array($f, $args);
}, 2);

$compose = function () {
	$fns = func_get_args();
	$c = 'call_user_func';
	switch (count($fns)) {
		case 0: throw new \InvalidArgumentException(
			'compose expects at least 1 parameter, 0 given'
		);
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
	$c   = 'call_user_func';
	// Optimized arity-specific
	switch (count($fns)) {
		case 0: throw new \InvalidArgumentException(
			'pipe expects at least 1 parameter, 0 given'
		);
		case 1: list($f) = $fns;
		return function ($x) use ($c, $f) {
			return $c($f, $x);
		};
		case 2: list($f, $g) = $fns;
		return function ($x) use ($c, $f, $g) {
			return $c($g, $c($f, $x));
		};
		case 3: list($f, $g, $h) = $fns;
		return function ($x) use ($c, $f, $g, $h) {
			return $c($h, $c($g, $c($f, $x)));
		};
		case 4: list($f, $g, $h, $i) = $fns;
		return function ($x) use ($c, $f, $g, $h, $i) {
			return $c($i, $c($h, $c($g, $c($f, $x))));
		};
		case 5: list($f, $g, $h, $i, $j) = $fns;
		return function ($x) use ($c, $f, $g, $h, $i, $j) {
			return $c($j, $c($i, $c($h, $c($g, $c($f, $x)))));
		};
	}
	return function ($x) use ($fns) {
		foreach ($fns as $f) {
			$x = call_user_func($f, $x);
		}
		return $x;
	};
};


$prop = function ($name) {
	return function ($el) use ($name) {
		return is_array($el)
				? $el[$name]
				: $el->$name;
	};
};

$where = function ($kvs, $strict = true) {
	if ($strict) {
		return function ($el) use ($kvs) {
			if (is_array($el)) {
				foreach ($kvs as $k => $v) {
					if ($el[$k] !== $v) {
						return false;
					}
				}
			} else {
				foreach ($kvs as $k => $v) {
					if ($el->$k !== $v) {
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
					if ($el[$k] != $v) {
						return false;
					}
				}
			} else {
				foreach ($kvs as $k => $v) {
					if ($el->$k != $v) {
						return false;
					}
				}
			}
			return true;
		};
	}
};

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

$drop = $curry(function ($n, $xs) {
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

$rest  = $tail = $drop(1);
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

$dropWhile = $curry(function ($f, $xs) {
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
		case 0: break;
		case 1:
			list($xs) = $args;
			if (is_string($xs)) {
				$xs = str_split($xs);
			}
			foreach ($xs as $x) {
				$out[] = call_user_func($f, $x);
			}
			break;
		case 2:
			list($xs, $ys) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			if (is_string($ys)) $ys = str_split($ys);
			reset($ys);
			foreach ($xs as $x) {
				if ($y = each($ys)) {
					$out[] = call_user_func($f, $x, $y['value']);
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
				if ($y = each($ys) &&
					$z = each($zs)) {
					$out[] = call_user_func($f, $x, $y['value'], $z['value']);
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
			}
			foreach ($xs as $x) {
				$vals = array($x);
				foreach ($args as $arg) {
					if ($v = each($arg)) {
						$vals[] = $v['value'];
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
		case 0: break;
		case 1:
			list($xs) = $args;
			if (is_string($xs)) $xs = str_split($xs);
			while ($x = each($xs)) {
				$out[] = call_user_func($f, $x);
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
				if ($y = each($ys) &&
					$z = each($zs)) {
					$out[] = call_user_func($f, $x, $y, $z);
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
			}
			while ($x = each($xs)) {
				$kvals = array($x);
				foreach ($args as $arg) {
					if ($kv = each($arg)) {
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
	global $concat, $map;
	$fn_with_args = func_get_args();
	return call_user_func($concat, call_user_func_array($map, $fn_with_args));
}, 2);
$mapcatkv = $curry(function () {
	global $concat, $map;
	$fn_with_args = func_get_args();
	return call_user_func($concat, call_user_func_array($mapkv, $fn_with_args));
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


foreach (get_defined_vars() as $k => $v) {
	if ($v instanceof \Closure) {
		F::set_fn($k, $v);
	}
}
