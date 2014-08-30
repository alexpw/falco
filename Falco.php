<?php
namespace Falco;

/**
 * ### Falco\Falco
 * a facade for calling Falco functions.
 */
final class Falco {

	/**
	 * **Falco::_** is a placeholder constant that can be used with partial() and thread() to
	 * control the placement of arguments.  Useful for when you want
	 * to use a 3rd party fn within a functional composition.
	 */
	const _ = '\Falco::_';

	private static $fns = array();

	/**
	 * load() is called for you to init the "core" module, but it's up to
	 * you and your application for what else is loaded.
	 */
	public static function load($module) {
		if (strpos($module, '/') === false) {
			$module = "module/{$module}.php";
		}
		if ((require_once $module) === 1) {
			unset($module);
			self::$fns = array_merge(self::$fns, get_defined_vars());
		}
	}

	/**
	 * set_fn() can be used to override existing fns or make new fns available.
	 */
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

Falco::set_fn('lazy', function ($xs) {
	if (is_string($xs)) $xs = str_split($xs);
	if (($xs instanceof \Iterator) === false) {
		return new \ArrayIterator($xs);
	}
	return $xs;
});
Falco::set_fn('value', function ($xs) {
	if ($xs instanceof \Iterator) {
		return iterator_to_array($xs);
	}
	return $xs;
});

/**
 * ### curry
 * A majority of core fns are curried and so they depend on this function being
 * available.
 */
Falco::set_fn('curry', function ($f, $numArgs = null) {
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
				return call_user_func_array($f, $args);
			}
			return $currier($args);
		};
	};
	return $currier(array());
});


// ### load
// Inject the core functions by default when this file is loaded.
require_once 'iterators/core.php';
Falco::load('core');
