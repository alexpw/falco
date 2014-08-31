<?php
namespace Falco\Support;

use Falco\Core as F;

/**
 * An internal implementation detail, not to be used directly.
 *
 * The use of "thread" here refers to Clojure's [->>](http://clojuredocs.org/clojure_core/clojure.core/-%3E%3E) and not to a thread of execution.
 */
final class Thread
{
    private $needle;

    public function __construct($needle)
    {
        $this->needle = $needle;
    }

    public function __call($method, $args)
    {
        // A pseudo-method that returns the transformed needle and ends the thread.
        if ($method === 'value') {
            return F::value($this->needle);
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
            $this->needle = call_user_func_array("Falco\Core::$method", $args);
            return $this;
        }
    }
}
