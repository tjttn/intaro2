<?php
//1
echo "Введите строку для задачи 1:\n";
$str = trim(fgets(STDIN));

$str = preg_replace_callback("/'(\\d+)'/", function ($m) {
    return "'" . ($m[1] * 2) . "'";
}, $str);

echo "Результат:\n";
echo $str . PHP_EOL;

//2
echo "\nВведите текст со ссылками для задачи 2:\n";
$text = trim(fgets(STDIN));

$text = preg_replace(
    "/https?:\/\/asozd\.duma\.gov\.ru\/main\.nsf\/\(Spravka\)\?OpenAgent&RN=([0-9\-]+)&\d+/",
    "http://sozd.parlament.gov.ru/bill/$1", $text
);

echo "Результат:\n";
echo $text . PHP_EOL;

?>