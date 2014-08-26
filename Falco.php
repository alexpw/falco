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

F::load('core');
