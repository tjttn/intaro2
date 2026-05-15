<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

class YandexGeocoder {
    private $apiKey;
    private $apiUrl = 'https://geocode-maps.yandex.ru/1.x/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function geocode($address) {
        $params = [
            'apikey' => $this->apiKey,
            'geocode' => $address,
            'format' => 'json',
            'results' => 1,
            'lang' => 'ru_RU'
        ];
        
        $url = $this->apiUrl . '?' . http_build_query($params);
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return ['success' => false, 'error' => 'Не удалось получить ответ от API'];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            return ['success' => false, 'error' => 'Ошибка API: ' . $data['error']['message']];
        }
        
        if (!isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
            return ['success' => false, 'error' => 'Адрес не найден'];
        }
        
        $geoObject = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];
        
        //координаты
        $pos = explode(' ', $geoObject['Point']['pos']);
        $coordinates = [
            'lon' => $pos[0],
            'lat' => $pos[1]
        ];
        
        //структурированный адрес
        $structuredAddress = $this->parseAddress($geoObject);
        
        //ближайшее метро
        $metro = $this->findNearestMetro($coordinates);
        
        return [
            'success' => true,
            'data' => [
                'structured' => $structuredAddress,
                'coordinates' => $coordinates['lat'] . ', ' . $coordinates['lon'],
                'metro' => $metro
            ]
        ];
    }
    
    private function parseAddress($geoObject) {
        $result = [
            'country' => '',
            'region' => '',
            'city' => '',
            'street' => '',
            'house' => '',
            'formatted' => $geoObject['name'] ?? ''
        ];
        
        // Пытаемся получить полный форматированный адрес
        if (isset($geoObject['metaDataProperty']['GeocoderMetaData']['text'])) {
            $result['formatted'] = $geoObject['metaDataProperty']['GeocoderMetaData']['text'];
        }
        
        // Разбираем компоненты адреса
        if (isset($geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'])) {
            foreach ($geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'] as $component) {
                switch ($component['kind']) {
                    case 'country':
                        $result['country'] = $component['name'];
                        break;
                    case 'province':
                        if (empty($result['region'])) {
                            $result['region'] = $component['name'];
                        }
                        break;
                    case 'locality':
                        $result['city'] = $component['name'];
                        break;
                    case 'street':
                        $result['street'] = $component['name'];
                        break;
                    case 'house':
                        $result['house'] = $component['name'];
                        break;
                }
            }
        }
        
        return $result;
    }
    
    private function findNearestMetro($coordinates) {
        // Ищем метро
        $params = [
            'apikey' => $this->apiKey,
            'geocode' => $coordinates['lon'] . ',' . $coordinates['lat'],
            'kind' => 'metro',
            'format' => 'json',
            'results' => 1,
            'lang' => 'ru_RU',
            'rspn' => 1,
            'spn' => '0.05,0.05' // ~5 км
        ];
        
        $url = $this->apiUrl . '?' . http_build_query($params);
        $response = $this->makeRequest($url);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if (isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
                $metroObject = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];
                $metroName = $metroObject['name'];
                
                // Получаем координаты метро
                $metroPos = explode(' ', $metroObject['Point']['pos']);
                
                // Вычисляем расстояние
                $distance = $this->calculateDistance(
                    floatval($coordinates['lat']),
                    floatval($coordinates['lon']),
                    floatval($metroPos[1]),
                    floatval($metroPos[0])
                );
                
                if ($distance <= 3000) { // Показываем только в радиусе 3 км
                    return $metroName . ' (' . round($distance / 1000, 1) . ' км)';
                }
            }
        }
        
        return null;
    }
    
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // метры
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'YandexGeocoder/1.0'
        ]);
        
        $response = curl_exec($ch);
        
        return $response;
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey = '5b340e3e-c3ff-4da5-a101-fd16b44e8439';
    
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    if (empty($address)) {
        echo json_encode([
            'success' => false,
            'error' => 'Адрес не может быть пустым'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $geocoder = new YandexGeocoder($apiKey);
    $result = $geocoder->geocode($address);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Метод не поддерживается'
    ], JSON_UNESCAPED_UNICODE);
}
?>