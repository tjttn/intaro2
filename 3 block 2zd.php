<?php
function processFiles($number) {
    $sectionsFile = $number . '_sections.xml';
    $productsFile = $number . '_products.xml';
    $outputFile = $number . '_output.xml';
    
    echo "\n=== Обработка теста $number ===\n";
    
    if (!file_exists($sectionsFile)) {
        echo "Предупреждение: Файл $sectionsFile не найден! Пропускаем.\n";
        return false;
    }
    if (!file_exists($productsFile)) {
        echo "Предупреждение: Файл $productsFile не найден! Пропускаем.\n";
        return false;
    }
    
    //чтение XML
    $sectionsXml = file_get_contents($sectionsFile);
    $productsXml = file_get_contents($productsFile);
    
    try {
        $sections = simplexml_load_string($sectionsXml);
        $products = simplexml_load_string($productsXml);
        
        if ($sections === false) {
            echo "Ошибка парсинга $sectionsFile\n";
            return false;
        }
        if ($products === false) {
            echo "Ошибка парсинга $productsFile\n";
            return false;
        }
        
        //результат XML
        $result = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ЭлементыКаталога></ЭлементыКаталога>');
        $resultSections = $result->addChild('Разделы');
        
        $productsBySection = [];
        
        if (isset($products->Товар) && $products->Товар) {
            foreach ($products->Товар as $product) {
                $productId = (string)$product->Ид;
                $productName = (string)$product->Наименование;
                $productSku = (string)$product->Артикул;
                
                $productInfo = [
                    'Ид' => $productId,
                    'Наименование' => $productName,
                    'Артикул' => $productSku
                ];
                
                if (isset($product->Разделы) && isset($product->Разделы->ИдРаздела)) {
                    foreach ($product->Разделы->ИдРаздела as $sectionId) {
                        $sectionIdStr = (string)$sectionId;
                        if (!isset($productsBySection[$sectionIdStr])) {
                            $productsBySection[$sectionIdStr] = [];
                        }
                        $productsBySection[$sectionIdStr][] = $productInfo;
                    }
                }
            }
        }
        
        //оздание разделов
        if (isset($sections->Раздел) && $sections->Раздел) {
            foreach ($sections->Раздел as $section) {
                $sectionId = (string)$section->Ид;
                $sectionName = (string)$section->Наименование;
                
                //доб раздел
                $resultSection = $resultSections->addChild('Раздел');
                $resultSection->addChild('Ид', $sectionId);
                $resultSection->addChild('Наименование', $sectionName);
                
                //доб товары
                $resultProducts = $resultSection->addChild('Товары');
                
                if (isset($productsBySection[$sectionId])) {
                    foreach ($productsBySection[$sectionId] as $productInfo) {
                        $resultProduct = $resultProducts->addChild('Товар');
                        $resultProduct->addChild('Ид', $productInfo['Ид']);
                        $resultProduct->addChild('Наименование', $productInfo['Наименование']);
                        $resultProduct->addChild('Артикул', $productInfo['Артикул']);
                    }
                }
            }
        }
        
        //вывод
        $dom = dom_import_simplexml($result)->ownerDocument;
        $dom->formatOutput = true;
        $output = $dom->saveXML();
        
        file_put_contents($outputFile, $output);
        
        echo "Успешно создан файл: $outputFile\n";
        return true;
        
    } catch (Exception $e) {
        echo "✗ Ошибка при обработке: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "Задача B: Объединение XML файлов\n";
echo "================================\n";

if ($argc > 1) {
    $number = $argv[1];
    if (is_numeric($number)) {
        $number = str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    processFiles($number);
} else {
    $successCount = 0;
    for ($i = 1; $i <= 6; $i++) {
        $number = str_pad($i, 3, '0', STR_PAD_LEFT);
        if (processFiles($number)) {
            $successCount++;
        }
    }
    echo "\n================================\n";
    echo "Обработано успешно: $successCount из 6 тестов\n";
}
?>