<?php
namespace Falco\Module;

use Falco\Core as F;
use Falco\Support\Thread;
use Falco\Iterator as Iter;

$lazy = function ($xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (($xs instanceof \Iterator) === false) {
        return new \ArrayIterator($xs);
    }
    return $xs;
};

$value = $val = function ($xs) {
    if ($xs instanceof \Iterator) {
        // iterator_to_array fails when combining Limit and Infinite iterators.
        // meaning, that it only makes it through 1 cycle, but can't rewind.
        if ($xs instanceof \LimitIterator) {
            $out = array();
            foreach ($xs as $k => $v) {
                if (is_string($k)) {
                    $out[$k] = $v;
                } else {
                    $out[] = $v;
                }
            }
            return $out;
        }
        return iterator_to_array($xs);
    }
    return $xs;
};

/**
 * ### curry
 * A majority of core fns are curried and so they depend on this function being
 * available.
 */
$curry = function ($f, $numArgs = null) {
    if ($numArgs === null) {
        if (is_array($f)) {
            $r = new \ReflectionMethod($f);
        } else {
            $r = new \ReflectionFunction($f);
        }
        $numArgs = $r->getNumberOfParameters();
    }
    // Optimize for small arity, minimizes fn wrapping.
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
                            return $f($x, $y, $z);
                        };
                    }
                    return call_user_func_array($f, $args);
                };
            } else if (count($args) === 2) {
                return function ($z) use ($args, $f) {
                    list($x, $y) = $args;
                    return $f($x, $y, $z);
                };
            }
            return call_user_func_array($f, $args);
        };
    }
    $currier = function ($partialArgs) use (& $currier, $f, $numArgs) {
        return function () use (& $currier, $f, $numArgs, $partialArgs) {
            $args = array_merge($partialArgs, func_get_args());
            if (count($args) >= $numArgs) {
                return call_user_func_array($f, $args);
            }
            return $currier($args);
        };
    };
    return $currier(array());
};

/**
 * ### thread
 * Absent macros that facilitate the elegance of clojure's [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E), instead, this emulates Underscore's [chain](http://underscorejs.org/#chain).
 */
$thread = function ($needle) {
    return new Thread($needle);
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
$addBy      = $curry(function ($n, $x) { return $x + $n; }, 2);
// ### subtractBy
$subtractBy = $curry(function ($n, $x) { return $x - $n; }, 2);
// ### multiplyBy
$multiplyBy = $curry(function ($n, $x) { return $x * $n; }, 2);
// ### divideBy
$divideBy   = $curry(function ($n, $x) { return $x / $n; }, 2);

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
    } else if (is_object($args[0])) {
        return array_sum(F::value($args[0]));
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
    } else if (is_object($args[0])) {
        return array_product(F::value($args[0]));
    }
    return array_product($args);
};
/**
 * ### all, every
 * `F::all(F::isOdd(), [1,2,3,4]);
 * => false
 * F::all(F::isOdd(), [1,3,5]);
 * => true`
 */
$all = $every = $curry(function ($f, $xs) {
    foreach ($xs as $x) {
        if (! $f($x)) {
             return false;
        }
    }
    return true;
}, 2);
/**
 * ### any, some
 * `F::any(F::isOdd(), [1,2,3,4]);
 * => true
 * F::any(F::isOdd(), [2,4,6]);
 * => false`
 */
$any = $some = $curry(function ($f, $xs) {
    foreach ($xs as $x) {
        if ($f($x)) {
            return true;
        }
    }
    return false;
}, 2);
/**
 * ### none
 * `F::none(F::isOdd(), [1,2,3,4]);
 * => false
 * F::none(F::isOdd(), [2,4,6]);
 * => true`
 */
$none = $curry(function ($f, $xs) {
    foreach ($xs as $x) {
        if ($f($x)) {
            return false;
        }
    }
    return true;
}, 2);

// ### eq
$eq = $curry(function () {
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

// ### lt
$lt = $curry(function () {
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
// ### lte
$lte = $curry(function () {
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

// ### gt
$gt = $curry(function () {
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
// ### gte
$gte = $curry(function () {
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

// ### not
$not = function ($f) {
    if (is_callable($f)) {
        return function ($x) use ($f) {
            return ! $f($x);
        };
    } else {
        return function ($x) use ($f) {
            return ! ($f === $x);
        };
    }
};

// ### andBy
$andBy = function () {
    $fns = func_get_args();
    switch (count($fns)) {
        case 1: list($f) = $fns;
            return function ($x) use ($f) {
                return $f($x);
            };
        case 2: list($f, $g) = $fns;
            return function ($x) use ($f, $g) {
                return $f($x) && $g($x);
            };
        case 3: list($f, $g, $h) = $fns;
            return function ($x) use ($f, $g, $h) {
                return $f($x) && $g($x) && $h($x);
            };
    }
    return function ($x) use ($fns) {
        return F::all(function ($f) use ($x) {
            return $f($x);
        }, $fns);
    };
};
// ### orBy
$orBy = function () {
    $fns = func_get_args();
    switch (count($fns)) {
        case 1: list($f) = $fns;
            return function ($x) use ($f) {
                return $f($x);
            };
        case 2: list($f, $g) = $fns;
            return function ($x) use ($f, $g) {
                return $f($x) || $g($x);
            };
        case 3: list($f, $g, $h) = $fns;
            return function ($x) use ($f, $g, $h) {
                return $f($x) || $g($x) || $h($x);
            };
    }
    return function ($x) use ($fns) {
        return ! F::none(function ($f) use ($x) {
            return $f($x);
        }, $fns);
    };
};

// ### count
$count = function ($xs) {
    if (is_string($xs)) return strlen($xs);
    if (is_array($xs)) return count($xs);
    if (is_object($xs)) {
        if ($xs instanceof Countable) {
            return count($xs);
        }
        return iterator_count($xs);
    }
    return 0;
};
// ### countBy
$countBy = $curry(function ($f, $xs) {
    if (is_string($xs)) return str_split($xs);
    if (is_object($xs)) $xs = F::value($xs);
    return array_reduce($xs, $f, 0);
}, 2);
// ### values
$values = function ($x) {
    if (is_array($x))  return array_values($x);
    if (is_object($x)) return array_values(get_object_vars($x));
    if (is_string($x)) return str_split($x);
};
// ### keys
$keys = function ($x) {
    if (is_array($x))  return array_keys($x);
    if (is_object($x)) return array_keys(get_object_vars($x));
    if (is_string($x)) return range(0, strlen($x));
};

#// ### toPairs
#$toPairs = function ($xs) {
#    if (is_string($xs)) return str_split($xs);
#    if (is_array($xs) || (is_object($xs) && $xs instanceof Traversable)) {
#        $out = array();
#        while ($pair = each($xs)) {
#            $out[] = $pair;
#        }
#        return $out;
#    }
#};
#// ### fromPairs
#$fromPairs = function ($xs) {
#    reset($xs);
#    $out = array();
#    while (list($key, $val) = $xs) {
#        $out[$key] = $val;
#    }
#    return $out;
#};

// ### sort
$sort    = function ($xs) { sort($xs);  return $xs; };
$ksort   = function ($xs) { ksort($xs); return $xs; };
$asort   = function ($xs) { asort($xs); return $xs; };
$sortBy  = $curry(function ($cmp, $in) { usort($in, $cmp);  return $in; }, 2);
$ksortBy = $curry(function ($cmp, $in) { uksort($in, $cmp); return $in; }, 2);
$asortBy = $curry(function ($cmp, $in) { uasort($in, $cmp); return $in; }, 2);

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

// ### juxt
$juxt = function () {
    $fns = func_get_args();
    return function ($x) use ($fns, $out) {
        $out = array();
        foreach ($fns as $f) {
            $out[] = $f($x);
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
    switch (count($fns)) {
        case 1: list($f) = $fns;
        return function ($x) use ($f) {
            #return $c($f, $x);
            return $f($x);
        };
        case 2: list($f, $g) = $fns;
        return function ($x) use ($f, $g) {
            #return $c($f, $c($g, $x));
            return $f($g($x));
        };
        case 3: list($f, $g, $h) = $fns;
        return function ($x) use ($f, $g, $h) {
            #return $c($f, $c($g, $c($h, $x));
            return $f($g($h($x)));
        };
        case 4: list($f, $g, $h, $i) = $fns;
        return function ($x) use ($f, $g, $h, $i) {
            #return $c($f, $c($g, $c($h, $c($i, $x))));
            return $f($g($h($i($x))));
        };
        case 5: list($f, $g, $h, $i, $j) = $fns;
        return function ($x) use ($f, $g, $h, $i, $j) {
            return $f($g($h($i($j($x)))));
        };
    }
    return function ($x) use ($fns) {
        for ($i = count($fns) - 1;  $i >= 0; $i--) {
            $x = $fns[$i]($x);
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
        $overNow = $over($overArg);
        return $used($overNow, $usedArg);
    }, 2);
};
// ### useUnder
$useUnder = function ($used, $under) {
    return F::curry(function ($usedArg, $underArg) use ($used, $under) {
        $underNow = $under($underArg);
        return $used($underNow, $usedArg);
    }, 2);
};

// ### useWith
$useWith = function () {
    $transformers = func_get_args();
    $fn = array_shift($transformers);
    return $curry(function () use ($fn, $transformers) {
        $args = func_get_args();
        foreach ($transformers as $i => $trans) {
            $args[$i] = $trans($args[$i]);
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
$prop = $nth = $curry(function ($name, $el) {
    return isset($el[$name]) ? $el[$name] : null;
}, 2);
// ### propEq
$propEq = $curry(function ($key, $val, $arr) {
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
$project = $curry(function ($names, $data) {
    return F::map(F::pick($names), $data);
}, 2);

// ### range
$range = 'range';

// ### lazyrange
$lazyrange = function ($from, $to = PHP_INT_MAX, $step = 1) {
    return new Iter\Range($from, $to, $step);
};

/**
 * ### where
 * @example 1
 * $data = [
 *  ['age' => 30, 'id' => 1],
 *  ['age' => 21, 'id' => 2]
 * ];
 * $age21 = F::where(['age' => 21]);
 * F::map($age21, $dat);
 * //=> [['age' => 21, 'id' => 2]]
 */
$where = function ($kvs, $strict = true) {
    if ($strict) {
        return function ($el) use ($kvs) {
            if (is_array($el)) {
                foreach ($kvs as $k => $v) {
                    if (is_callable($v)) {
                        if (! $v($el[$k])) {
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
                        if (! $v($el->$k)) {
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
                        if (! $v($el[$k])) {
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
                        if (! $v($el->$k)) {
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
$take = $curry(function ($n, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $cnt = 0;
        $out = array();
        foreach ($xs as $k => $x) {
            $out[$k] = $x;
            $cnt++;
            if ($cnt >= $n) {
                break;
            }
        }
        return $out;
    } else if (is_object($xs)) {
        return new \LimitIterator($xs, 0, $n);
    }
}, 2);

// ### first, head
$first = $head = function ($xs) {
    $rs = F::value(F::take(1, $xs));
    return reset($rs);
};
// ### ffirst
$ffirst = $compose($first, $first);

// ### last
$last = function ($xs) {
    if (is_string($xs)) return $xs[strlen($xs) - 1];
    if (is_array($xs))  return $xs[count($xs) - 1];
    if (is_object($xs)) {
        $arr = F::value($xs);
        return $arr[count($arr) - 1];
    }
};

// ### skip
$skip = $curry(function ($n, $xs) {
    switch (gettype($xs)) {
        case 'array':  return array_slice($xs, $n);
        case 'string': return substr($xs, $n);
        case 'object':
            if ($xs instanceof \Iterator) {
                foreach ($xs as $x) {
                    next($xs);
                    $n--;
                    if ($n <= 0) {
                        break;
                    }
                }
                return $xs;
            }
    }
}, 2);

// ### rest
$rest  = $tail = $skip(1);
// ### frest
$frest = $compose($first, $rest);

// ### takeWhile
$takeWhile = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        foreach ($xs as $k => $x) {
            if (! $f($x)) {
                break;
            }
            $out[$k] = $x;
        }
        return $out;
    } else if (is_object($xs)) {
        return new Iter\TakeWhile($f, $xs);
    }
}, 2);

// ### takeUntil
$takeUntil = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        foreach ($xs as $k => $x) {
            if ($f($x)) {
                $out[$k] = $x;
                return;
            }
            $out[$k] = $x;
        }
        return $out;
    } else if (is_object($xs)) {
        return new Iter\TakeUntil($f, $xs);
    }
}, 2);

// ### skipWhile
$skipWhile = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $i = 0;
        foreach ($xs as $k => $x) {
            if ($f($x)) {
                $i++;
            } else {
                break;
            }
        }
        return array_slice($xs, 0, $i);
    } else if (is_object($xs)) {
        return new Iter\SkipWhile($f, $xs);
    }
}, 2);

// ### skipUntil
$skipUntil = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $i = 0;
        foreach ($xs as $k => $x) {
            if (! $f($x)) {
                $i++;
            } else {
                $i++; break;
            }
        }
        return array_slice($out, 0, $i);
    } else if (is_object($xs)) {
        return new Iter\SkipUntil($f, $xs);
    }
}, 2);

// ### repeat
$repeat = $curry(function ($el, $times) {
    if ($times === 0) {
        return array();
    }
    return array_fill(0, $times, $el);
}, 2);

// ### zipmap
$zipmap = $curry('array_combine', 2);

// ### iterate
$iterate = $curry(function ($f, $x) {
    return new Iter\Iterate($f, $x);
}, 2);

$cycle = function ($xs) {
    $iter = F::lazy($xs);
    return new \InfiniteIterator($iter);
};

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
    } else {
        $concatter = new \AppendIterator();
        foreach ($args as $arg) {
            $concatter->append($arg);
        }
        return $concatter;
    }
};

// ### map
$map = $curry(function () {
    $args = func_get_args();
    $f    = array_shift($args);

    if (is_object($args[0])) {
        return new Iter\Map($f, $args);
    }

    switch (count($args)) {
        case 1:
            list($xs) = $args;
            if (is_string($xs)) $xs = str_split($xs);
            if (is_array($xs)) {
                $out  = array();
                foreach ($xs as $k => $x) {
                    $out[$k] = $f($x);
                }
                return $out;
            }
            break;
        case 2:
            list($xs, $ys) = $args;
            if (is_string($xs)) $xs = str_split($xs);
            if (is_string($ys)) $ys = str_split($ys);
            reset($ys);
            $out = array();
            foreach ($xs as $x) {
                if ($y = each($ys)) {
                    $out[] = $f($x, $y[1]);
                } else {
                    break;
                }
            }
            return $out;
        case 3:
            list($xs, $ys, $zs) = $args;
            if (is_string($xs)) $xs = str_split($xs);
            if (is_string($ys)) $ys = str_split($ys);
            if (is_string($zs)) $zs = str_split($zs);
            reset($ys); reset($zs);
            $out = array();
            foreach ($xs as $x) {
                if (list(, $yv) = each($ys)) {
                    if (list(, $zv) = each($zs)) {
                        $out[] = $f($x, $yv, $zv);
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            }
            return $out;
        default:
            $numArgs = count($args);
            $xs = array_shift($args);
            if (is_string($xs)) $xs = str_split($xs);
            foreach ($args as $i => $arg) {
                if (is_string($arg)) $args[$i] = str_split($arg);
                else reset($arg);
            }

            $out = array();
            $vs  = array();
            foreach ($xs as $x) {
                $vs[0] = $x;
                for ($j = 1; $j < $numArgs; $j++) {
                    if (list(, $v) = each($args[$j - 1])) {
                       $vs[$j] = $v;
                    } else {
                        break 2;
                    }
                }
                switch ($numArgs) {
                    case 4:
                        $out[] = $f($vs[0], $vs[1], $vs[2], $vs[3]);
                        break;
                    case 5:
                        $out[] = $f($vs[0], $vs[1], $vs[2], $vs[3], $vs[4]);
                        break;
                    default:
                    $out[] = call_user_func_array($f, $vs);
                }
            }
            return $out;
    }
}, 2);

// ### mapcat, flatMap
$mapcat = $flatMap = $curry(function () {
    $fn_with_args = func_get_args();
    return F::concat(call_user_func_array(F::map(), $fn_with_args));
}, 2);

// ### filter
$filter = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        foreach ($xs as $k => $x) {
            if ($f($x)) {
                $out[$k] = $x;
            }
        }
        return $out;
    } else if (is_object($xs)) {
        return new Iter\Filter($f, $xs);
    }
}, 2);
// ### filterkv
$filterkv = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        while ($x = each($xs)) {
            if ($f($x)) {
                $out[$x[0]] = $x[1];
            }
        }
        return $out;
    }
}, 2);

// ### ffilter
$ffilter   = $compose($first, $filter);
// ### ffilterkv
$ffilterkv = $compose($first, $filterkv);

// ### remove
$remove = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        foreach ($xs as $k => $x) {
            if (! $f($x)) {
                $out[$k] = $x;
            }
        }
        return $out;
    } else if (is_object($xs)) {
        return new Iter\Filter($f, $xs, $ok = false);
    }
}, 2);
// ### removekv
$removekv = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    $out = array();
    while ($x = each($xs)) {
        if (! $f($x)) {
            $out[$x[0]] = $x[1];
        }
    }
    return $out;
}, 2);

// ### reduce, fold, foldl
$reduce = $fold = $foldl = $curry(function () {
    $args = func_get_args();
    $f    = array_shift($args);
    if (count($args) === 1) {
        $xs           = $args[0];
        $initialValue = array_shift($xs);
    } else {
        list($initialValue, $xs) = $args;
    }
    if (is_string($xs)) $xs = str_split($xs);
    return array_reduce($xs, $f, $initialValue);
}, 2);
// ### reducekv, foldkv
$reducekv = $foldkv = $curry(function () {
    $args = func_get_args();
    $f    = array_shift($args);
    if (count($args) === 1) {
        $xs           = $args[0];
        $initialValue = array_shift($xs);
    } else {
        list($initialValue, $xs) = $args;
    }
    if (is_string($xs)) $xs = str_split($xs);
    $accumulator = $initialValue;
    foreach ($xs as $k => $v) {
        $accumulator = $f($accumulator, $k, $v);
    }
    return $accumulator;
}, 2);
// ### reduceRight, foldr
$reduceRight = $foldr = $curry(function () {
    $args = func_get_args();
    $f    = array_shift($args);
    if (count($args) === 1) {
        $xs           = $args[0];
        $initialValue = array_shift($xs);
    } else {
        list($initialValue, $xs) = $args;
    }
    if (is_string($xs)) $xs = str_split($xs);
    $xs = array_reverse($xs);
    return array_reduce($xs, $f, $initialValue);
}, 2);
// ### foldrkv
$foldrkv = $curry(function () {
    $args = func_get_args();
    $f    = array_shift($args);
    if (count($args) === 1) {
        $xs           = $args[0];
        $initialValue = array_shift($xs);
    } else {
        list($initialValue, $xs) = $args;
    }
    if (is_string($xs)) $xs = str_split($xs);
    $accumulator = $initialValue;
    $xs = array_reverse($xs);
    foreach ($xs as $k => $v) {
        $accumulator = $f($accumulator, $k, $v);
    }
    return $accumulator;
}, 2);

$frequencies = 'array_count_values';

// ### partitionBy
$partitionBy = $curry(function ($f, $xs) {
    if (is_string($xs)) $xs = str_split($xs);
    if (is_array($xs)) {
        $out = array();
        $flag = null;
        $group = array();
        foreach ($xs as $x) {
            $truthy = $f($x);
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
    }
    return new Iter\PartitionBy($f, $xs);
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
                        $kval = $keyFn($n[$keyKey]);
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
                                    $val[$valKey] = $valFn($n[$valKey]);
                                }
                            } else if (isset($n[$valFn])) {
                                $valKey = $valFn;
                                $val[$valKey] = $n[$valKey];
                            }
                        }
                        $ref[$kval] = $val;
                    } else if (is_callable($mixedVals)) {
                        $ref[$kval] = $mixedVals($n);
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
                            $val[$valKey] = $valFn($n[$valKey]);
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
                $out[$n[$key]] = $mixedVals($n);
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
                        $kval = $keyFn($n[$keyKey]);
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
                                    $val[$valKey] = $valFn($n[$valKey]);
                                }
                            } else if (isset($n[$valFn])) {
                                $valKey = $valFn;
                                $val[$valKey] = $n[$valKey];
                            }
                        }
                        $ref[$kval][] = $val;
                    } else if (is_callable($mixedVals)) {
                        $ref[$kval][] = $mixedVals($n);
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
                            $val[$valKey] = $valFn($n[$valKey]);
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
                $out[$n[$key]][] = $mixedVals($n);
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
$memoize = function ($fn, $cacheKeyFn = 'json_encode', $limit = null) {
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
