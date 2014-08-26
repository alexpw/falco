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

	public static function load($module) {
		if ((require_once "module/{$module}.php") === 1) {
			unset($module);
			self::$fns = array_merge(self::$fns, get_defined_vars());
		}
	}

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

F::set_fn('curry', function ($f, $numArgs = null) {
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
});

F::load('core');
