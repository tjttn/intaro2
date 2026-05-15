<?php

function restoreUrl($input) {
    $protocols = ['https', 'http'];
    $domains = ['ru', 'com'];
    
    $bestUrl = '';
    $bestProtocolLen = -1;
    $bestHostLen = PHP_INT_MAX;
    
    foreach ($protocols as $protocol) {
        $protocolLen = strlen($protocol);
        
        foreach ($domains as $domain) {
            $domainLen = strlen($domain);
            $inputLen = strlen($input);
            
            for ($i = 0; $i < $inputLen; $i++) {
                if (substr($input, $i, $protocolLen) !== $protocol) {
                    continue;
                }
                
                $pos = $i + $protocolLen;
                if ($pos + 3 > $inputLen || substr($input, $pos, 3) !== '://') {
                    continue;
                }
                
                $pos += 3;
                
                // Собираем домен (строчные латинские буквы)
                $hostStart = $pos;
                $hostEnd = $hostStart;
                while ($hostEnd < $inputLen && ctype_lower($input[$hostEnd])) {
                    $hostEnd++;
                }
                
                if ($hostEnd == $hostStart) {
                    continue;
                }
                
                // Проверяем точку
                if ($hostEnd >= $inputLen || $input[$hostEnd] !== '.') {
                    continue;
                }
                
                $pos = $hostEnd + 1; // после точки (aaa.aaa)
                
                if ($pos + $domainLen > $inputLen || 
                    substr($input, $pos, $domainLen) !== $domain) {
                    continue;
                }
                
                $domainEnd = $pos + $domainLen;
                $host = substr($input, $hostStart, $hostEnd - $hostStart);
                $hostLen = $hostEnd - $hostStart;
                
                //контекст после слеша
                $context = '';
                $contextStart = $domainEnd;
                
                if ($contextStart < $inputLen && $input[$contextStart] === '/') {
                    $contextStart++;
                    $contextEnd = $contextStart;
                    while ($contextEnd < $inputLen && ctype_lower($input[$contextEnd])) {
                        $contextEnd++;
                    }
                    $context = substr($input, $contextStart, $contextEnd - $contextStart);
                }
                
                //создание URL
                $url = $protocol . '://' . $host . '.' . $domain;
                if (!empty($context)) {
                    $url .= '/' . $context;
                }
                
                //сравниваем вариант
                if ($protocolLen > $bestProtocolLen || 
                    ($protocolLen == $bestProtocolLen && $hostLen < $bestHostLen)) {
                    $bestUrl = $url;
                    $bestProtocolLen = $protocolLen;
                    $bestHostLen = $hostLen;
                }
            }
        }
    }
    
    return $bestUrl;
}

//тестирование
$testCases = [
    'https1c-bitrixproducts' => 'https://1c-bitrix.ru/products',
    'httpgooglecomsearch' => 'http://google.com/search',
    'httpsmysiterupage' => 'https://mysite.ru/page',
    'httpabccom' => 'http://abc.com',
    'httpsgithubcom' => 'https://github.com',
    'httpstestcomapi' => 'https://test.com/api',
    'httpsverylongdomainnamecompath' => 'https://verylongdomainname.com/path',
    'httpab' => 'http://a.ru/b',
    'httpsaaabbbruccc' => 'https://aaabbb.ru/ccc',
    'httpzzzcom' => 'http://zzz.com',
];

foreach ($testCases as $input => $expected) {
    $result = restoreUrl($input);
    echo "Input: $input\n";
    echo "Result: $result\n";
    echo "Expected: $expected\n";
    echo "Status: " . ($result === $expected ? "✓ PASS\n" : "✗ FAIL\n");
    echo "---\n";
}

function processFileA($inputFile, $outputFile) {
    $input = trim(file_get_contents($inputFile));
    $result = restoreUrl($input);
    file_put_contents($outputFile, $result);
}

function generateTestsA($count = 30) {
    $dir = 'tests_a';
    if (!is_dir($dir)) mkdir($dir);
    
    $protocols = ['http', 'https'];
    $domains = ['ru', 'com'];
    $hosts = ['a', 'ab', 'abc', 'mysite', 'google', 'github', 'yandex', 
              'verylongdomainname', 'test', 'api', 'developer', '1c-bitrix'];
    $contexts = ['', 'page', 'search', 'products', 'api', 'docs', 'path', 
                 'category', 'item', 'user', 'admin', 'index'];
    
    for ($i = 0; $i < $count; $i++) {
        $protocol = $protocols[array_rand($protocols)];
        $host = $hosts[array_rand($hosts)];
        $domain = $domains[array_rand($domains)];
        $context = $contexts[array_rand($contexts)];
        
        $broken = $protocol . $host . $domain;
        if (!empty($context)) {
            $broken .= $context;
        }
        
        $expected = $protocol . '://' . $host . '.' . $domain;
        if (!empty($context)) {
            $expected .= '/' . $context;
        }
        
        file_put_contents("$dir/input_$i.txt", $broken);
        file_put_contents("$dir/expected_$i.txt", $expected);
        
        echo "Generated test $i: $broken -> $expected\n";
    }
}

generateTestsA(30);
?>