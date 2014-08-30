<?php

require '../Falco.php';

use Falco\Falco as F;

$tasks = [
    ['username' => 'Michael', 'title' => 'Curry stray functions', 'dueDate' => '2014-05-06', 'complete' => true, 'effort' => 'low', 'priority' => 'low'],    
    ['username' => 'Scott',   'title' => 'Add `fork` function', 'dueDate' => '2014-05-14', 'complete' => true, 'effort' => 'low', 'priority' => 'low'],    
    ['username' => 'Michael', 'title' => 'Write intro doc', 'dueDate' => '2014-05-16', 'complete' => true, 'effort' => 'low', 'priority' => 'low'],    
    ['username' => 'Michael', 'title' => 'Add modulo function', 'dueDate' => '2014-05-17', 'complete' => false, 'effort' => 'low', 'priority' => 'low'],    
    ['username' => 'Michael', 'title' => 'Separating generators', 'dueDate' => '2014-05-24', 'complete' => false, 'effort' => 'medium', 'priority' => 'medium'],
    ['username' => 'Scott',   'title' => 'Fold algebra branch back in', 'dueDate' => '2014-06-01', 'complete' => false, 'effort' => 'low', 'priority' => 'low'],
    ['username' => 'Scott',   'title' => 'Fix `and`/`or`/`not`', 'dueDate' => '2014-06-05', 'complete' => false, 'effort' => 'low', 'priority' => 'low'],
    ['username' => 'Michael', 'title' => 'Types infrastucture', 'dueDate' => '2014-06-06', 'complete' => false, 'effort' => 'medium', 'priority' => 'high'],
    ['username' => 'Scott',   'title' => 'Add `mapObj`', 'dueDate' => '2014-06-09', 'complete' => false, 'effort' => 'low', 'priority' => 'medium'], 
    ['username' => 'Scott',   'title' => 'Write using doc', 'dueDate' => '2014-06-11', 'complete' => false, 'effort' => 'medium', 'priority' => 'high'],
    ['username' => 'Michael', 'title' => 'Finish algebraic types', 'dueDate' => '2014-06-15', 'complete' => false, 'effort' => 'high', 'priority' => 'high'],
    ['username' => 'Scott',   'title' => 'Determine versioning scheme', 'dueDate' => '2014-06-15', 'complete' => false, 'effort' => 'low', 'priority' => 'medium'],
    ['username' => 'Michael', 'title' => 'Integrate types with main code', 'dueDate' => '2014-06-22', 'complete' => false, 'effort' => 'medium', 'priority' => 'high'],
    ['username' => 'Richard', 'title' => 'API documentation', 'dueDate' => '2014-06-22', 'complete' => false, 'effort' => 'high', 'priority' => 'medium'],
    ['username' => 'Scott',   'title' => 'complete build system', 'dueDate' => '2014-06-22', 'complete' => false, 'effort' => 'medium', 'priority' => 'high'],
    ['username' => 'Richard', 'title' => 'Overview documentation', 'dueDate' => '2014-06-25', 'complete' => false, 'effort' => 'medium', 'priority' => 'high'],
];

$incomplete        = F::filter(F::where(['complete' => false]));
$sortByDate        = F::sortBy(F::prop('dueDate'));
$sortByDateDescend = F::compose(F::reverse(), $sortByDate);
$groupByUser       = F::groupBy('username', ['title', 'dueDate']);
$activeByUser      = F::compose($groupByUser, $incomplete);
$gloss             = F::compose(F::take(5), $sortByDateDescend);
$topData           = F::compose($gloss, $incomplete);
$topDataAllUsers   = F::compose(F::map($gloss), $activeByUser);
$byUser            = F::useOver(F::filter(), F::propEq('username'));

$scottResults = $topData($byUser('Scott', $tasks));
var_export($scottResults);
echo "-------------------\n";
$allResults = $topDataAllUsers($tasks);
var_export($allResults);

