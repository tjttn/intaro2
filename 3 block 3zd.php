<?php
function createFunnelImage($goals, $events) {
    $width = 600;
    $height = 600;
    
    //создаем изображение
    $image = imagecreatetruecolor($width, $height);
    
    $colors = [
        imagecolorallocate($image, 255, 100, 100),
        imagecolorallocate($image, 100, 255, 100),
        imagecolorallocate($image, 100, 100, 255),
        imagecolorallocate($image, 255, 255, 100),
        imagecolorallocate($image, 255, 100, 255),
        imagecolorallocate($image, 100, 255, 255),
        imagecolorallocate($image, 255, 200, 100),
        imagecolorallocate($image, 200, 100, 255),
        imagecolorallocate($image, 100, 200, 255),
        imagecolorallocate($image, 255, 150, 200)
    ];
    
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    
    $userProgress = [];
    $stageCounts = array_fill(0, count($goals), 0);
    
    $userEvents = [];
    foreach ($events as $event) {
        list($userId, $goalId) = explode(' ', $event);
        if (!isset($userEvents[$userId])) {
            $userEvents[$userId] = [];
        }
        $userEvents[$userId][] = $goalId;
    }
    
    // Для каждого пользователя определяем максимальный достигнутый этап
    foreach ($userEvents as $userId => $userGoalEvents) {
        $maxStage = -1;
        foreach ($userGoalEvents as $eventGoal) {
            // Находим индекс цели в последовательности
            $stageIndex = array_search($eventGoal, $goals);
            if ($stageIndex !== false && $stageIndex > $maxStage) {
                $maxStage = $stageIndex;
            }
        }
        
        // Увеличиваем счетчик для всех пройденных этапов
        for ($i = 0; $i <= $maxStage; $i++) {
            $stageCounts[$i]++;
        }
    }
    
    // Определяем максимальное количество для масштабирования
    $maxCount = max($stageCounts);
    if ($maxCount == 0) $maxCount = 1;
    
    //параметры воронки
    $topWidth = $width - 100;  // 500px
    $bottomWidth = 80;          // 80px
    $funnelHeight = $height - 100; // 500px
    
    $segmentHeight = $funnelHeight / count($goals);
    
    // Рисуем сегменты воронки
    for ($i = 0; $i < count($goals); $i++) {
        $y1 = 50 + $i * $segmentHeight;
        $y2 = $y1 + $segmentHeight;
        
        // Пропорциональная ширина сегмента
        $ratio = ($i == 0) ? 1 : (float)$stageCounts[$i] / $stageCounts[0];
        $currentWidth = $topWidth * $ratio;
        
        $x1 = ($width - $currentWidth) / 2;
        $x2 = $width - $x1;
        
        // Для следующего сегмента
        $nextRatio = ($i + 1 < count($goals)) ? (float)$stageCounts[$i + 1] / $stageCounts[0] : (float)$stageCounts[$i] / $stageCounts[0];
        $nextWidth = $topWidth * $nextRatio;
        $nextX1 = ($width - $nextWidth) / 2;
        $nextX2 = $width - $nextX1;
        
        // Заливка сегмента
        $color = $colors[$i % count($colors)];
        
        // Рисуем трапецию
        $points = [
            $x1, $y1,
            $x2, $y1,
            $nextX2, $y2,
            $nextX1, $y2
        ];
        imagefilledpolygon($image, $points, 4, $color);
        
        // Рисуем границу
        imagepolygon($image, $points, 4, $black);
        
        // Добавляем текст с названием цели и количеством
        $text = $goals[$i] . " (" . $stageCounts[$i] . ")";
        $textBox = imagettfbbox(12, 0, __DIR__ . '/arial.ttf', $text);
        if ($textBox === false) {
            // Если шрифт не найден, используем встроенный
            $textX = $width / 2 - (strlen($text) * 6) / 2;
            $textY = $y1 + $segmentHeight / 2 + 5;
            imagestring($image, 3, $textX, $textY, $text, $white);
        } else {
            $textWidth = $textBox[2] - $textBox[0];
            $textHeight = $textBox[1] - $textBox[7];
            $textX = $width / 2 - $textWidth / 2;
            $textY = $y1 + $segmentHeight / 2 + $textHeight / 2;
            imagettftext($image, 12, 0, $textX, $textY, $white, __DIR__ . '/arial.ttf', $text);
        }
    }
    
    //сохраняем изображение
    imagepng($image, 'output.png');
    imagedestroy($image);
}

//чтение входных данных
$input = file_get_contents('php://stdin');
$lines = explode("\n", trim($input));

$n = (int)$lines[0];
$goals = [];

for ($i = 1; $i <= $n; $i++) {
    $goals[] = trim($lines[$i]);
}

$m = (int)$lines[$n + 1];
$events = [];

for ($i = $n + 2; $i < $n + 2 + $m; $i++) {
    if (isset($lines[$i]) && trim($lines[$i]) != '') {
        $events[] = trim($lines[$i]);
    }
}

//создаем изображение воронки
createFunnelImage($goals, $events);

echo "Image created successfully: output.png\n";
?>