<?php
$csv = array_map('str_getcsv', file('../rewrites.csv'));

$devUrls = ['www.dev.petpark.sk/'];

$prodUrls = ['www.petpark.sk/','www.petpark.cz/'];

foreach ($prodUrls as $key => $mainUrl) {
    foreach ($csv as $cv) {
        $url = explode(';', $cv[0]);

        $handle = curl_init($mainUrl.$url[0]);

        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_NOBODY, 1);

        $response = curl_exec($handle);

        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if($httpCode == 404) {
            $code = $httpCode." ----> " . $url[0] . PHP_EOL;
            echo $code;
            $codes .= $code;
        }

        curl_close($handle);
    }
    $lang = ($key == 0) ? 'sk' : 'cz';
    file_put_contents('../urls-report-'.$lang.'.txt', $codes);
}
