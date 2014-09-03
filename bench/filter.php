<?php
ini_set('memory_limit', '1G');

require dirname(dirname(__FILE__)) .'/vendor/autoload.php';
use Falco\Core as F;

require dirname(__FILE__) .'/Timer.php';
$t = new \Timer;

$max = 500000;
$limit = isset($argv[1]) ? $argv[1]+0 : 10000;
$xs  = range(1, $max);
$lxs = F::lazy($xs);
$odd = F::isOdd();

$rs  = array();
$run = $t->start("control strict $max");

foreach ($xs as $x) {
    if ($x % 2 === 1) {
        $rs[] = $x;
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("control closure $max");

foreach ($xs as $x) {
    if ($odd($x)) {
        $rs[] = $x;
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("filter strict $max");
$rs  = F::filter($odd, $xs);
$t->end($run);

$rs  = array();
$run = $t->start("filter lazy $max");
$rs  = F::value(F::filter($odd, $lxs));
$t->end($run);

$rs  = array();
$run = $t->start("control strict $limit \$i");

$i = 0;
foreach ($xs as $x) {
    if ($x % 2 === 1) {
        $i++;
        $rs[] = $x;
        if ($i === $limit) {
            break;
        }
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("control strict $limit count");

foreach ($xs as $x) {
    if ($x % 2 === 1) {
        $rs[] = $x;
        if (count($rs) === $limit) {
            break;
        }
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("control closure $limit");

foreach ($xs as $x) {
    if ($odd($x)) {
        $rs[] = $x;
        if (count($rs) === $limit) {
            break;
        }
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("filter strict $limit ($max) foreach");

foreach (F::filter($odd, $xs) as $x) {
    $rs[] = $x;
    if (count($rs) === $limit) {
        break;
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("filter strict $limit ($max) take");
$rs  = F::take($limit, F::filter($odd, $xs));
$t->end($run);

$rs  = array();
$run = $t->start("filter lazy $limit foreach \$i");
$i = 0;
foreach (F::filter($odd, $lxs) as $x) {
    $rs[] = $x;
    $i++;
    if ($i === $limit) {
        break;
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("filter lazy $limit foreach count");
foreach (F::filter($odd, $lxs) as $x) {
    $rs[] = $x;
    if (count($rs) === $limit) {
        break;
    }
}
$t->end($run);

$rs  = array();
$run = $t->start("filter lazy $limit take");
$rs  = F::value(F::take($limit, F::filter($odd, $lxs)));
$t->end($run);

$rs  = array();
$run = $t->start("filter lazyrange $limit foreach");

foreach(F::filter($odd, F::lazyrange(1, $limit * 2)) as $x) {
    $rs[] = $x;
}
$t->end($run);

$rs  = array();
$run = $t->start("filter lazyrange $limit value");
$rs  = F::value(F::filter($odd, F::lazyrange(1, $limit * 2)));
$t->end($run);

$rs  = array();
$run = $t->start("filter lazyrange $limit take");
$rs  = F::value(F::take($limit, F::filter($odd, F::lazyrange(1, $limit * 2))));
$t->end($run);

echo $t;
