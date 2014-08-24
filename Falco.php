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

$identity = function ($x) { return $x; };

$always = function ($x) {
	return function () use ($x) {
		$args = func_get_args();
		return $x;
	};
};
$alwaysTrue  = $always(true);
$alwaysFalse = $always(false);
$alwaysNull  = $always(null);

$isOdd    = function ($x) { return abs($x) % 2 === 1; };
$isEven   = function ($x) { return abs($x) % 2 === 0; };
$isTruthy = function ($x) { return !! $x; };
$isFalsey = function ($x) { return ! $x; };
$isEmpty  = function ($x) { return empty($x); };
$isPositive = function ($x) { return $x > 0; };
$isNegative = function ($x) { return $x < 0; };
$isZero     = function ($x) { return $x === 0; };

$min = function () { return call_user_func_array('min', func_get_args()); };
$max = function () { return call_user_func_array('max', func_get_args()); };

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
			$args = func_get_args();
			if (count($args) === 1) {
				list($x) = $args;
				return call_user_func($f, $x);
			}
			return call_user_func_array($f, $args);
		};
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

$addBy = $curry(function ($n, $x) {
	return $x + $n;
}, 2);
$subtractBy = $curry(function ($n, $x) {
	return $x - $n;
}, 2);
$multiplyBy = $curry(function ($n, $x) {
	return $x * $n;
}, 2);
$divideBy = $curry(function ($n, $x) {
	return $x / $n;
}, 2);

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

$not = function ($f) {
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
$opNot = $not;

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
	$c   = 'call_user_func';
	switch (count($fns)) {
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
		return isset($el[$name]) ? $el[$name] : null;
	};
};
$props = function ($names) {
	return function ($el) use ($names) {
		return isset($el[$name]) ? $el[$name] : null;
	};
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

$groupBy = $curry(function ($f, $xs) {
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
 * A variation of group_by that presumes that only ONE element with $in
 * will match the $key, so that it can provide a quick mapping of:
 * element[key] => element;
 *
 * The $valKeysOrFn optionally restricts which keys are allowed in the
 * mapped element.
 *
 * @examples
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
 * indexBy(['name', 'id'], null $in)
 * => ['alex' => [3 => ['id' => 3, 'name' => 'alex']],
 *     'john' => [5 => ['id' => 5, 'name' => 'john']]]
 */
$indexBy = $curry(function ($keys, $valKeysOrFn, $in) {
	$out = array();

	// pre-compute the flip
	if (is_array($valKeysOrFn)) {
		$valKeys = array_flip($valKeysOrFn);
	}

	if (is_string($keys)) {
		$key = $keys;
		if (is_null($valKeysOrFn)) {
			foreach ($in as $n) {
				$out[$n[$key]] = $n;
			}
		} else if (is_array($valKeysOrFn)) {
			foreach ($in as $n) {
				$val = array_intersect_key($n, $valKeys);
				if (count($val)) {
					$out[$n[$key]] = $val;
				}
			}
		} else if (is_callable($valKeysOrFn)) {
			foreach ($in as $n) {
				$val = call_user_func($valKeysOrFn, $n);
				if ($val !== null) {
					$out[$n[$key]] = $val;
				}
			}
		} else {
			foreach ($in as $n) {
				if (isset($n[$valKeysOrFn])) {
					$out[$n[$key]] = $n[$valKeysOrFn];
				}
			}
		}
	} else if (is_array($keys)) {
		$last_idx  = count($keys) - 1;

		foreach ($in as $n) {

			$ref =& $out;
			foreach ($keys as $i => $key) {

				$kval = null;
				if (is_string($key)) {
					if (isset($n[$key])) {
						$kval = $n[$key];
					}
				} else if (is_callable($key)) {
					$kval = call_user_func($key, $n);
				}

				if ($kval === null) {
					break;
				}

				if (! isset($ref[$kval])) {
					$ref[$kval] = array();
				}

				if ($i === $last_idx) {
					if (is_null($valKeysOrFn)) {
						$ref[$kval] = $n;
					} else if (is_array($valKeysOrFn)) {
						$val = array_intersect_key($n, $valKeys);
						if (count($val)) {
							$ref[$kval] = $val;
						}
					} else if (is_callable($valKeysOrFn)) {
						$val = call_user_func($valKeysOrFn, $n);
						if ($val !== null) {
							$ref[$kval] = $val;
						}
					} else if (isset($n[$valKeysOrFn])) {
						$ref[$kval] = $n[$valKeysOrFn];
					}
				} else {
					$ref =& $ref[$kval];
				}
			}
		}
	} else if (is_callable($keys)) {

		foreach ($in as $n) {

			$ckeys = call_user_func($keys, $n);
			if ($ckeys === null) {
				continue;
			}
			else if (is_array($ckeys)) {

				$ref =& $out;
				foreach ($ckeys as $i => $key) {

					$kval = null;
					if (is_string($key)) {
						if (isset($n[$key])) {
							$kval = $n[$key];
						}
					} else if (is_callable($key)) {
						$kval = call_user_func($key, $n);
					}

					if ($kval === null) {
						break;
					}

					if (! isset($ref[$kval])) {
						$ref[$kval] = array();
					}

					if ($i === $last_idx) {
						if (is_null($valKeysOrFn)) {
							$ref[$kval] = $n;
						} else if (is_array($valKeysOrFn)) {
							$val = array_intersect_key($n, $valKeys);
							if (count($val)) {
								$ref[$kval] = $val;
							}
						} else if (is_callable($valKeysOrFn)) {
							$val = call_user_func($valKeysOrFn, $n);
							if ($val !== null) {
								$ref[$kval] = $val;
							}
						} else if (isset($n[$valKeysOrFn])) {
							$ref[$kval] = $n[$valKeysOrFn];
						}
					} else {
						$ref =& $ref[$kval];
					}
				}
			}
			else {
				$kval = $ckeys;
				if (is_null($valKeysOrFn)) {
					$out[$kval] = $n;
				}
				else if (is_array($valKeysOrFn)) {
					$val = array_intersect_key($n, $valKeys);
					if (count($val)) {
						$out[$kval] = $val;
					}
				} else if (is_callable($valKeysOrFn)) {
					$val = call_user_func($valKeysOrFn, $n);
					if ($val !== null) {
						$out[$kval] = $val;
					}
				} else if (isset($n[$valKeysOrFn])) {
					$out[$kval] = $n[$valKeysOrFn];
				}
			}
		}
	} else {
		$out = $in;
	}
	return $out;
}, 3);

foreach (get_defined_vars() as $k => $v) {
	if ($v instanceof \Closure) {
		F::set_fn($k, $v);
	}
}