<?php

function solve($input) {

    $data = preg_split('/\s+/', trim($input));
    $p = 0;

    $n = intval($data[$p++]);
    $m = intval($data[$p++]);

    $INF = 1000000000;

    $graph = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $graph[$i][$j] = ($i == $j) ? 0 : $INF;
        }
    }

    for ($i = 0; $i < $m; $i++) {
        $a = intval($data[$p++]);
        $b = intval($data[$p++]);
        $w = intval($data[$p++]);

        $graph[$a][$b] = $w;
        $graph[$b][$a] = $w;
    }

    $k = intval($data[$p++]);

    $dist = [];

    $recalc = function() use (&$graph, &$dist, $n, $INF) {

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $dist[$i][$j] = $graph[$i][$j];
            }
        }

        for ($k = 0; $k < $n; $k++) {
            for ($i = 0; $i < $n; $i++) {
                for ($j = 0; $j < $n; $j++) {

                    if ($dist[$i][$k] + $dist[$k][$j] < $dist[$i][$j]) {
                        $dist[$i][$j] = $dist[$i][$k] + $dist[$k][$j];
                    }

                }
            }
        }
    };

    $recalc();

    $out = [];

    for ($i = 0; $i < $k; $i++) {

        $c = intval($data[$p++]);
        $d = intval($data[$p++]);
        $r = $data[$p++];

        if ($r === "?") {

            if ($dist[$c][$d] >= $INF)
                $out[] = -1;
            else
                $out[] = $dist[$c][$d];

        }
        elseif ($r === "-1") {

            $graph[$c][$d] = $INF;
            $graph[$d][$c] = $INF;

            $recalc();

        }
        else {

            $w = intval($r);

            $graph[$c][$d] = $w;
            $graph[$d][$c] = $w;

            $recalc();

        }
    }

    return implode("\n", $out);
}



if (isset($argv[1]) && $argv[1] == "test") {

    for ($t = 1; $t <= 9; $t++) {

        $num = str_pad($t, 3, "0", STR_PAD_LEFT);

        $dat = "$num.dat";
        $ans = "$num.ans";

        if (!file_exists($dat)) {
            echo "Test $t - NO DATA\n";
            continue;
        }

        $input = file_get_contents($dat);
        $result = trim(solve($input));

        if (file_exists($ans)) {

            $expected = trim(file_get_contents($ans));

            if ($result === $expected) {
                echo "Test $t - OK\n";
            } else {
                echo "Test $t - FAIL\n";
            }

        } else {

            echo "Test $t - NO ANSWER FILE\n";

        }
    }

}
else {

    $input = file_get_contents("php://stdin");
    echo solve($input);

}