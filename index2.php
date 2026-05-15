<?php

$input = file_get_contents('php://stdin');
$lines = explode("\n", trim($input));

$nodes = [];
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 4) continue;
    
    $nodes[] = [
        'id' => $parts[0],
        'name' => $parts[1],
        'l' => (int)$parts[2],
        'r' => (int)$parts[3]
    ];
}

usort($nodes, fn($a, $b) => $a['l'] - $b['l']);

$result = [];
$path = [];

foreach ($nodes as $node) {
    $depth = 0;
    foreach ($path as $p) {
        if ($p['l'] < $node['l'] && $p['r'] > $node['r']) {
            $depth++;
        }
    }
    
    $result[] = str_repeat('-', $depth) . $node['name'];
    
    while (!empty($path) && end($path)['r'] < $node['r']) {
        array_pop($path);
    }
    $path[] = $node;
}

echo implode("\n", $result);