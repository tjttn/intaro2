<?php
$txt_files = glob('*.txt');

foreach ($txt_files as $filename) {
    echo "$filename\n";
    
    $content = file_get_contents($filename);
    $blocks = explode("\n\n", trim($content));
    
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        
        $inputLines = explode("\n", trim($block));
        
        $banners = [];
        $total_shows = pow(10,6);
        
        foreach ($inputLines as $input) {
            $temp = preg_split('/\s+/', trim($input));
            if (count($temp) < 2) continue;
            
            $banner_id = $temp[0];
            $weight = (float)$temp[1];
            
            $banners[] = [
                'id' => $banner_id,
                'weight' => $weight,
                'shows' => 0
            ];
        }
        
        $total_weight = 0;
        foreach ($banners as $banner) {
            $total_weight += $banner['weight'];
        }
        
        for ($i = 0; $i < $total_shows; $i++) {
            $rand = mt_rand(1, $total_weight);
            $current_sum = 0;
            
            foreach ($banners as &$banner) {
                $current_sum += $banner['weight'];
                if ($rand <= $current_sum) {
                    $banner['shows']++;
                    break;
                }
            }
        }
        
        unset($banner);
        
        foreach ($banners as $banner) {
            $proportion = $banner['shows'] / $total_shows;
            echo $banner['id'] . " " . number_format($proportion, 6, '.', '') . "\n";
        }
        
        echo "\n";
    }
    
    echo "------------------------\n\n";
}
?>