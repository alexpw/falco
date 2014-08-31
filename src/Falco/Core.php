<?php
namespace Falco;

/**
 * ### Falco\Core
 * a facade for calling Falco functions.
 */
final class Core
{
    /**
     * **Falco::_** is a placeholder constant that can be used with partial() and thread() to
     * control the placement of arguments.  Useful for when you want
     * to use a 3rd party fn within a functional composition.
     */
    const _ = 'Falco\Core::_';

    public static $fns = array();

    public static function __callStatic($method, $args)
    {
        if (empty($args)) {
            return self::$fns[$method];
        }
        return call_user_func_array(self::$fns[$method], $args);
    }
}
