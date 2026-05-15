<?php

function expand($ip) {

    if (strpos($ip, "::") !== false) {

        $parts = explode("::", $ip);

        $left = $parts[0] == "" ? [] : explode(":", $parts[0]);
        $right = $parts[1] == "" ? [] : explode(":", $parts[1]);

        $missing = 8 - (count($left) + count($right));

        $mid = array_fill(0, $missing, "0");

        $blocks = array_merge($left, $mid, $right);

    } else {

        $blocks = explode(":", $ip);
    }

    for ($i = 0; $i < 8; $i++) {
        $blocks[$i] = str_pad($blocks[$i], 4, "0", STR_PAD_LEFT);
    }

    return implode(":", $blocks);
}


if (isset($argv[1]) && $argv[1] == "test") {

    for ($t = 1; $t <= 8; $t++) {

        $num = str_pad($t, 3, "0", STR_PAD_LEFT);

        $lines = file($num.".dat", FILE_IGNORE_NEW_LINES);
        $answers = file($num.".ans", FILE_IGNORE_NEW_LINES);

        $ok = true;

        for ($i = 0; $i < count($lines); $i++) {

            $res = expand(trim($lines[$i]));

            if ($res != trim($answers[$i])) {
                $ok = false;
                echo "Тест $num строка ".($i+1)." ошибка\n";
                echo "Результат программы: $res\n";
                echo "Ответ : ".$answers[$i]."\n\n";
            }
        }

        if ($ok) {
            echo "Тест $num ок\n";
        }
    }

} else {

    $lines = file("input.txt", FILE_IGNORE_NEW_LINES);

    foreach ($lines as $ip) {
        echo expand(trim($ip))."\n";
    }

}