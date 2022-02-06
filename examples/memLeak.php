<?php

declare(strict_types=1);

use JsonMachine\Items;

require_once __DIR__.'/../../vendor/autoload.php';

ini_set('memory_limit', 128 * 1024 * 1024);

function dummy()
{
    $i = 0;
    $string = file_get_contents(__DIR__.'/../../test/performance/twitter_example_0.json');
    $item = '['.str_repeat($string.',', 400).$string.']';
    var_dump(strlen($item));

    yield '[';
    $sep = '';
    while ($i++ < 100) {
        yield $sep;
        $sep = ',';
        yield $item;
    }
    yield ']';
}

$items = Items::fromIterable(dummy());
$previousReport = '';
foreach ($items as $i => $item) {
    $report = memory_get_peak_usage()
        .':'.memory_get_peak_usage(true)
    ;

    if ($report !== $previousReport) {
        $index = str_pad($i, 3, ' ', STR_PAD_LEFT);
        echo "$index: $report\n";
        $previousReport = $report;
    }

    unset($item);
}
