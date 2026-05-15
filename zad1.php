<?php

if (isset($argv[1]) && $argv[1] == "test") {

    for ($t = 1; $t <= 8; $t++) {

        $num = str_pad($t, 3, "0", STR_PAD_LEFT);

        $data = preg_split('/\s+/', file_get_contents($num.".dat"));
        $i = 0;

        $n = $data[$i++];

        $bets = [];
        for ($j = 0; $j < $n; $j++) {
            $a = $data[$i++];
            $s = $data[$i++];
            $r = $data[$i++];
            $bets[] = [$a,$s,$r];
        }

        $m = $data[$i++];

        $games = [];
        for ($j = 0; $j < $m; $j++) {
            $b = $data[$i++];
            $c = $data[$i++];
            $d = $data[$i++];
            $k = $data[$i++];
            $tres = $data[$i++];
            $games[$b] = [$c,$d,$k,$tres];
        }

        $balance = 0;

        foreach ($bets as $b) {

            $g = $games[$b[0]];

            if ($b[2] == $g[3]) {
                if ($b[2] == "L") $balance += $b[1]*$g[0]-$b[1];
                if ($b[2] == "R") $balance += $b[1]*$g[1]-$b[1];
                if ($b[2] == "D") $balance += $b[1]*$g[2]-$b[1];
            } else {
                $balance -= $b[1];
            }
        }

        $ans = trim(file_get_contents($num.".ans"));

        if ($balance == $ans) {
            echo "Тест $num: пройден (answer = $balance)\n";
        } else {
            echo "Тест $num: не пройден\n";
            echo "Результат программы: $balance\n";
            echo "Ответ: $ans\n\n";
        }
    }

} else {

    $data = preg_split('/\s+/', file_get_contents("input.txt"));
    $i = 0;

    $n = $data[$i++];

    for ($j = 0; $j < $n; $j++) {
        $a = $data[$i++];
        $s = $data[$i++];
        $r = $data[$i++];
        $bets[] = [$a,$s,$r];
    }

    $m = $data[$i++];

    for ($j = 0; $j < $m; $j++) {
        $b = $data[$i++];
        $c = $data[$i++];
        $d = $data[$i++];
        $k = $data[$i++];
        $t = $data[$i++];
        $games[$b] = [$c,$d,$k,$t];
    }

    $balance = 0;

    foreach ($bets as $b) {

        $g = $games[$b[0]];

        if ($b[2] == $g[3]) {
            if ($b[2] == "L") $balance += $b[1]*$g[0]-$b[1];
            if ($b[2] == "R") $balance += $b[1]*$g[1]-$b[1];
            if ($b[2] == "D") $balance += $b[1]*$g[2]-$b[1];
        } else {
            $balance -= $b[1];
        }
    }

    echo $balance;
}