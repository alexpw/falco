<?php

use Falco\Core;

$dir = dirname($file);
$ds  = DIRECTORY_SEPARATOR;

require "{$dir}{$ds}Module{$ds}Core.php";

unset($file, $dir, $ds);

Core::$fns = get_defined_vars();
