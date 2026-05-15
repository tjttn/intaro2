<?php
// task_d.php - Генерация Sitemap

class SitemapGenerator {
    private $sections = [];
    private $maxTimes = [];
    
    public function addSection($id, $url, $parentId, $timestamp) {
        $this->sections[$id] = [
            'id' => $id,
            'url' => $url,
            'parent_id' => $parentId,
            'time' => $timestamp,
            'children' => []
        ];
    }
    
    public function buildTree() {
        // Строим дерево
        $roots = [];
        foreach ($this->sections as $id => &$section) {
            if ($section['parent_id'] == 0) {
                $roots[$id] = &$section;
            } else {
                if (isset($this->sections[$section['parent_id']])) {
                    $this->sections[$section['parent_id']]['children'][$id] = &$section;
                }
            }
        }
        
        // Вычисляем максимальное время для каждого раздела (включая потомков)
        foreach ($this->sections as $id => &$section) {
            $this->maxTimes[$id] = $this->calculateMaxTime($id);
        }
        
        return $roots;
    }
    
    private function calculateMaxTime($id) {
        if (!isset($this->sections[$id])) {
            return 0;
        }
        
        $maxTime = $this->sections[$id]['time'];
        
        foreach ($this->sections[$id]['children'] as $childId => $child) {
            $childMaxTime = $this->calculateMaxTime($childId);
            $maxTime = max($maxTime, $childMaxTime);
        }
        
        return $maxTime;
    }
    
    public function generateSitemap() {
        $this->buildTree();
        
        // Сортируем разделы по ID
        ksort($this->sections);
        
        $xml = '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($this->sections as $id => $section) {
            $lastmod = $this->formatTimestamp($this->maxTimes[$id]);
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . htmlspecialchars($section['url']) . '</loc>' . "\n";
            $xml .= '<lastmod>' . $lastmod . '</lastmod>' . "\n";
            $xml .= '</url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    private function formatTimestamp($timestamp) {
        // Формат ISO 8601 с часовым поясом +03:00
        $datetime = new DateTime('@' . $timestamp);
        $datetime->setTimezone(new DateTimeZone('+0300'));
        return $datetime->format('Y-m-d\TH:i:sP');
    }
    
    public function parseInput($input) {
        $lines = explode("\n", trim($input));
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $parts = explode(';', trim($line));
            if (count($parts) >= 4) {
                $id = (int)$parts[0];
                $url = $parts[1];
                $parentId = (int)$parts[2];
                $timestamp = (int)$parts[3];
                $this->addSection($id, $url, $parentId, $timestamp);
            }
        }
    }
}

// Тестирование
$testInput = "6;https://site.org/;0;1548933472
3;https://site.org/contacts/;6;1549521832
1;https://site.org/vacancy/;3;1548933472";

$generator = new SitemapGenerator();
$generator->parseInput($testInput);
$sitemap = $generator->generateSitemap();

echo $sitemap . "\n";

// Функция для обработки файлового ввода
function processFileD($inputFile, $outputFile) {
    $input = file_get_contents($inputFile);
    $generator = new SitemapGenerator();
    $generator->parseInput($input);
    $sitemap = $generator->generateSitemap();
    file_put_contents($outputFile, $sitemap);
}

// Генерация тестовых файлов
function generateTestsD($count = 20) {
    $dir = 'tests_d';
    if (!is_dir($dir)) mkdir($dir);
    
    $urls = [
        '/', '/about/', '/contacts/', '/products/', '/services/',
        '/blog/', '/news/', '/catalog/', '/support/', '/docs/',
        '/vacancy/', '/reviews/', '/faq/', '/api/', '/admin/'
    ];
    
    $domains = ['site.org', 'example.com', 'mysite.ru', 'company.net', 'portal.ru'];
    
    for ($t = 0; $t < $count; $t++) {
        $numSections = rand(3, 15);
        $sections = [];
        $domain = $domains[array_rand($domains)];
        $baseUrl = "https://$domain";
        
        // Сначала создаем корневые разделы
        $rootIds = [];
        $nextId = 1;
        $numRoots = rand(1, min(3, $numSections));
        
        for ($i = 0; $i < $numRoots; $i++) {
            $id = $nextId++;
            $url = $baseUrl . $urls[array_rand($urls)];
            $time = rand(1548933472, 1549521832);
            $sections[] = "$id;$url;0;$time";
            $rootIds[] = $id;
        }
        
        // Добавляем дочерние разделы
        while ($nextId <= $numSections) {
            $parentId = $rootIds[array_rand($rootIds)];
            $id = $nextId++;
            $url = $baseUrl . $urls[array_rand($urls)];
            $time = rand(1548933472, 1549521832);
            $sections[] = "$id;$url;$parentId;$time";
            
            // Иногда добавляем внуков
            if (rand(0, 2) == 0 && $nextId <= $numSections) {
                $childId = $nextId++;
                $childUrl = $baseUrl . $urls[array_rand($urls)];
                $childTime = rand(1548933472, 1549521832);
                $sections[] = "$childId;$childUrl;$id;$childTime";
            }
        }
        
        // Перемешиваем для реалистичности
        shuffle($sections);
        $input = implode("\n", $sections);
        
        file_put_contents("$dir/input_$t.txt", $input);
        
        // Генерируем ожидаемый вывод
        $generator = new SitemapGenerator();
        $generator->parseInput($input);
        $sitemap = $generator->generateSitemap();
        file_put_contents("$dir/expected_$t.xml", $sitemap);
        
        echo "Generated test $t with " . count($sections) . " sections\n";
    }
}

generateTestsD(20);
?>