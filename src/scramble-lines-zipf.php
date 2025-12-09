<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php zipf-sample.php <file> <count> [seed] [alpha]\n");
    exit(1);
}

$file  = $argv[1];
$count = intval($argv[2]);
$seed  = $argc >= 4 ? intval($argv[3]) : 12345;
$alpha = $argc >= 5 ? floatval($argv[4]) : 1.0;

$lines = file($file, FILE_IGNORE_NEW_LINES);
$n = count($lines);

if ($n === 0) {
    fwrite(STDERR, "Input file is empty.\n");
    exit(1);
}

// deterministic float 0–1
function seeded_rand_float($key, $seed) {
    $h = hash('sha256', $seed . "-" . $key);
    return hexdec(substr($h, 0, 12)) / 0xFFFFFFFFFFFF;
}

//
// --------------------------------------
// 1. DETERMINISTIC FISHER–YATES SHUFFLE
// --------------------------------------
//
for ($i = $n - 1; $i > 0; $i--) {
    $u = seeded_rand_float("shuffle-$i", $seed);
    $j = (int) floor($u * ($i + 1));

    // swap
    $tmp = $lines[$i];
    $lines[$i] = $lines[$j];
    $lines[$j] = $tmp;
}

//
// --------------------------------------
// 2. BUILD ZIPF WEIGHTS / CDF
// --------------------------------------
//
$weights = [];
$sum = 0.0;

for ($i = 1; $i <= $n; $i++) {
    $w = 1.0 / pow($i, $alpha);
    $sum += $w;
    $weights[$i] = $sum;
}

// normalize to 1.0
for ($i = 1; $i <= $n; $i++) {
    $weights[$i] /= $sum;
}

//
// --------------------------------------
// 3. SAMPLE WITH REPLACEMENT
// --------------------------------------
//
for ($k = 1; $k <= $count; $k++) {
    $u = seeded_rand_float("draw-$k", $seed);

    // binary search the CDF
    $lo = 1;
    $hi = $n;
    while ($lo < $hi) {
        $mid = intdiv($lo + $hi, 2);
        if ($weights[$mid] >= $u) {
            $hi = $mid;
        } else {
            $lo = $mid + 1;
        }
    }

    echo $lines[$lo - 1], "\n";
}
