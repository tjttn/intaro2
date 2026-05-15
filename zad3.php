<?php

function validate($value, $rule, $p1 = null, $p2 = null) {

    switch ($rule) {
        case "S":
            $len = mb_strlen($value, '8bit');
            return ($len >= $p1 && $len <= $p2) ? "OK" : "FAIL";

        case "N":
            $trimmed = trim($value);
            if (!preg_match('/^-?\d+$/', $trimmed)) return "FAIL";
            $num = intval($trimmed);
            return ($num >= $p1 && $num <= $p2) ? "OK" : "FAIL";

        case "P":
            $trimmed = trim($value);
            return preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/', $trimmed) ? "OK" : "FAIL";

        case "D":
            $trimmed = trim($value);
            
            if (!preg_match('/^\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}$/', $trimmed)) {
                return "FAIL";
            }
            
            $parts = preg_split('/[. :]/', $trimmed);
            if (count($parts) != 5) return "FAIL";
            
            list($day, $month, $year, $hour, $minute) = $parts;
            
            if (!checkdate((int)$month, (int)$day, (int)$year)) return "FAIL";
            
            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) return "FAIL";
            
            return "OK";

        case "E":
            $trimmed = trim($value);
            
            if (!preg_match('/^([a-zA-Z0-9_]{4,30})@([a-zA-Z0-9.-]{2,30})\.([a-z]{2,10})$/', $trimmed, $m)) {
                return "FAIL";
            }
            
            $name = $m[1];
            $domainBody = $m[2];
            
            if (strpos($name, '__') !== false) return "FAIL";
            
            if ($name[0] === '_') return "FAIL";
            
            if (strpos($domainBody, '_') !== false) return "FAIL";
            
            if (strpos($domainBody, '--') !== false) return "FAIL";
            
            if ($domainBody[0] === '-' || substr($domainBody, -1) === '-') return "FAIL";
            
            if (strpos($domainBody, '..') !== false) return "FAIL";
            
            return "OK";lstat


        default:
            return "FAIL";
    }
}

if (isset($argv[1]) && $argv[1] == "test") {
    for ($t = 1; $t <= 14; $t++) {
        $num = str_pad($t, 3, "0", STR_PAD_LEFT);
        if (!file_exists($num.".dat") || !file_exists($num.".ans")) continue;

        $lines = file($num.".dat", FILE_IGNORE_NEW_LINES);
        $answers = file($num.".ans", FILE_IGNORE_NEW_LINES);
        $res_array = []; $ans_array = [];

        foreach ($lines as $i => $line) {
            if (trim($line) === "" && $line !== "0") continue;
            if (preg_match('/<(.*)> (\S)(.*)/', $line, $m)) {
                $paramsStr = trim($m[3]);
                $params = preg_split('/\s+/', $paramsStr, -1, PREG_SPLIT_NO_EMPTY);
                $p1 = $params[0] ?? null;
                $p2 = $params[1] ?? null;
                $res_array[] = validate($m[1], $m[2], $p1, $p2);
                $ans_array[] = trim($answers[$i] ?? '');
            }
        }
        echo "Test $num:\nExpected: ".implode(" ", $ans_array)."\nYour    : ".implode(" ", $res_array)."\n\n";
    }
} else {
    $input = file_exists("input.txt") ? file("input.txt", FILE_IGNORE_NEW_LINES) : [];
    foreach ($input as $line) {
        if (trim($line) === "" && $line !== "0") continue;
        if (preg_match('/<(.*)> (\S)(.*)/', $line, $m)) {
            $paramsStr = trim($m[3]);
            $params = preg_split('/\s+/', $paramsStr, -1, PREG_SPLIT_NO_EMPTY);
            echo validate($m[1], $m[2], $params[0] ?? null, $params[1] ?? null)."\n";
        }
    }
}