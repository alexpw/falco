<?php

use Falco\Core;

$dir = dirname($file);
$ds  = DIRECTORY_SEPARATOR;

require "{$dir}{$ds}Module{$ds}Core.php";

unset($file, $dir, $ds);

$fns = get_defined_vars();
foreach ($fns as $name => $f) {
    $fns[strtolower($name)] = $f;
}
Core::$fns = $fns;
