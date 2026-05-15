<?php
$input = file_get_contents('php://stdin');
$lines = explode("\n", trim($input));

$n = (int)$lines[0];
$results = [];

for ($i = 1; $i <= $n; $i++) {
    if (empty($lines[$i])) continue;
    
    //дата_время_вылета часовой_пояс_вылета дата_время_прибытия часовой_пояс_прибытия
    $parts = preg_split('/\s+/', trim($lines[$i]));
    
    $departureStr = $parts[0];
    $departureTz = (int)$parts[1];
    $arrivalStr = $parts[2];
    $arrivalTz = (int)$parts[3];
    
    //дата и время вылета (формат: d.m.Y_H:i:s)
    $departureDateTime = str_replace('_', ' ', $departureStr);
    $departureTimestamp = strtotime($departureDateTime);
    
    //датв и время прибытия
    $arrivalDateTime = str_replace('_', ' ', $arrivalStr);
    $arrivalTimestamp = strtotime($arrivalDateTime);
    
    //превод в UTC
    $departureUTC = $departureTimestamp - ($departureTz * 3600);
    $arrivalUTC = $arrivalTimestamp - ($arrivalTz * 3600);
    
    //время полета в секундах
    $flightTime = $arrivalUTC - $departureUTC;
    
    $results[] = $flightTime;
}

foreach ($results as $result) {
    echo $result . "\n";
}
?>