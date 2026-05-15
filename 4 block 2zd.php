<?php
// task_b_fixed.php

function ipToInt($ip) {
    $parts = explode('.', $ip);
    return ((int)$parts[0] << 24) | 
           ((int)$parts[1] << 16) | 
           ((int)$parts[2] << 8) | 
           (int)$parts[3];
}

function intToIp($int) {
    return (($int >> 24) & 0xFF) . '.' .
           (($int >> 16) & 0xFF) . '.' .
           (($int >> 8) & 0xFF) . '.' .
           ($int & 0xFF);
}

function findSubnetMask($ips, $k) {
    $intIps = array_map('ipToInt', $ips);
    sort($intIps);
    
    // Пробуем маски от 32 до 0
    for ($maskLen = 32; $maskLen >= 0; $maskLen--) {
        $mask = ~((1 << (32 - $maskLen)) - 1) & 0xFFFFFFFF;
        
        $networks = [];
        foreach ($intIps as $ip) {
            $network = $ip & $mask;
            $networks[$network] = true;
            if (count($networks) > $k) {
                break;
            }
        }
        
        if (count($networks) <= $k) {
            // Нашли маску, которая дает не более k сетей
            // Теперь нужно максимизировать длину (минимизировать количество сетей)
            while ($maskLen < 32) {
                $nextMaskLen = $maskLen + 1;
                $nextMask = ~((1 << (32 - $nextMaskLen)) - 1) & 0xFFFFFFFF;
                $nextNetworks = [];
                foreach ($intIps as $ip) {
                    $network = $ip & $nextMask;
                    $nextNetworks[$network] = true;
                    if (count($nextNetworks) > $k) {
                        break 2;
                    }
                }
                if (count($nextNetworks) <= $k) {
                    $maskLen = $nextMaskLen;
                    $mask = $nextMask;
                } else {
                    break;
                }
            }
            return intToIp($mask);
        }
    }
    
    return '255.255.255.255';
}

function findSubnetMaskAlternative($ips, $k) {
    $intIps = array_map('ipToInt', $ips);
    sort($intIps);
    
    //если k >= n, то каждый IP в своей сети
    if ($k >= count($ips)) {
        return '255.255.255.255';
    }
    
    //находим биты, которые различаются
    $differBit = 32;
    for ($i = 0; $i < count($intIps) - 1; $i++) {
        $xor = $intIps[$i] ^ $intIps[$i + 1];
        if ($xor > 0) {
            $bitPos = 32 - floor(log($xor, 2)) - 1;
            $differBit = min($differBit, $bitPos);
        }
    }
    
    //маска должна быть такой, чтобы получилось не более k сетей
    for ($maskLen = $differBit; $maskLen >= 0; $maskLen--) {
        $mask = ~((1 << (32 - $maskLen)) - 1) & 0xFFFFFFFF;
        $networks = [];
        foreach ($intIps as $ip) {
            $network = $ip & $mask;
            $networks[$network] = true;
            if (count($networks) > $k) {
                break;
            }
        }
        
        if (count($networks) <= $k) {
            //увеличиваем длину маски если возмонжо
            while ($maskLen < 32) {
                $testMaskLen = $maskLen + 1;
                $testMask = ~((1 << (32 - $testMaskLen)) - 1) & 0xFFFFFFFF;
                $testNetworks = [];
                foreach ($intIps as $ip) {
                    $network = $ip & $testMask;
                    $testNetworks[$network] = true;
                    if (count($testNetworks) > $k) {
                        break 2;
                    }
                }
                if (count($testNetworks) <= $k) {
                    $maskLen = $testMaskLen;
                    $mask = $testMask;
                } else {
                    break;
                }
            }
            return intToIp($mask);
        }
    }
    
    return '255.255.255.255';
}

//находим маску, при которой количество различных сетей равно k
function findSubnetMaskCorrect($ips, $k) {
    $intIps = array_map('ipToInt', $ips);
    sort($intIps);
    
    $bestMask = 0;
    $bestMaskLen = 0;
    
    //возможные длины маски
    for ($maskLen = 0; $maskLen <= 32; $maskLen++) {
        $mask = ~((1 << (32 - $maskLen)) - 1) & 0xFFFFFFFF;
        $networks = [];
        
        foreach ($intIps as $ip) {
            $network = $ip & $mask;
            $networks[$network] = true;
        }
        
        $networkCount = count($networks);
        
        if ($networkCount <= $k && $maskLen > $bestMaskLen) {
            $bestMaskLen = $maskLen;
            $bestMask = $mask;
        }
    }
    
    return intToIp($bestMask);
}

// Тестирование
$testCases = [
    [
        'ips' => ['0.0.0.1', '0.1.1.2', '0.0.2.1', '0.1.1.0', '0.0.2.3'],
        'k' => 3,
        'expected' => '255.255.254.0'
    ],
    [
        'ips' => ['192.168.1.1', '192.168.1.2', '192.168.1.3'],
        'k' => 1,
        'expected' => '255.255.255.0'
    ],
    [
        'ips' => ['10.0.0.1', '10.0.0.2', '10.0.1.1', '10.0.1.2'],
        'k' => 2,
        'expected' => '255.255.254.0'  // 10.0.0.x и 10.0.1.x - две сети
    ],
    [
        'ips' => ['1.1.1.1', '2.2.2.2', '3.3.3.3'],
        'k' => 3,
        'expected' => '255.255.255.255'
    ],
    [
        'ips' => ['192.168.1.1', '192.168.1.2', '192.168.2.1', '192.168.2.2'],
        'k' => 2,
        'expected' => '255.255.254.0'
    ],
];

foreach ($testCases as $test) {
    $result = findSubnetMaskCorrect($test['ips'], $test['k']);
    echo "IPs: " . implode(', ', $test['ips']) . "\n";
    echo "k = {$test['k']}\n";
    echo "Result: $result\n";
    echo "Expected: {$test['expected']}\n";
    echo "Status: " . ($result === $test['expected'] ? "✓ PASS\n" : "✗ FAIL\n");
    echo "---\n";
}

// Функция для работы с файлами
function processFileB($inputFile, $outputFile) {
    $content = file_get_contents($inputFile);
    $lines = explode("\n", trim($content));
    list($n, $k) = explode(' ', $lines[0]);
    $ips = array_slice($lines, 1, (int)$n);
    $result = findSubnetMaskCorrect($ips, (int)$k);
    file_put_contents($outputFile, $result);
}

// Генерация тестов
function generateTestsB($count = 30) {
    $dir = 'tests_b';
    if (!is_dir($dir)) mkdir($dir);
    
    for ($t = 0; $t < $count; $t++) {
        $n = rand(2, 50);
        $k = rand(1, min($n, 8));
        
        //генерируем IP из нескольких подсетей
        $subnets = rand(1, $k);
        $ips = [];
        
        for ($s = 0; $s < $subnets; $s++) {
            $subnetBase = rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
            $ipsInSubnet = rand(1, max(1, ceil($n / $subnets)));
            
            for ($i = 0; $i < $ipsInSubnet && count($ips) < $n; $i++) {
                $lastOctet = rand(1, 254);
                $ips[] = $subnetBase . '.' . $lastOctet;
            }
        }
        
        shuffle($ips);
        $ips = array_slice($ips, 0, $n);
        
        $input = "$n $k\n" . implode("\n", $ips);
        file_put_contents("$dir/input_$t.txt", $input);
        
        $result = findSubnetMaskCorrect($ips, $k);
        file_put_contents("$dir/expected_$t.txt", $result);
        
        echo "Generated test $t: n=$n, k=$k, subnets=$subnets, result=$result\n";
    }
}

generateTestsB(30);
?>