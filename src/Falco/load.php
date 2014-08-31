<?php

use Falco\Core;

$dir = dirname($file);
require "$dir/Module/Core.php";
unset($file, $dir);

Core::$fns = get_defined_vars();
