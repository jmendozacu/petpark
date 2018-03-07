<?php
$csv = array_map('str_getcsv', file('../rewrites.csv'));


$devUrl = 'www.dev.petpark.sk/';
$prodUrl = 'www.petpark.sk/';

foreach ($csv as $cv) {
    $url = explode(';', $cv[0]);

    $handle = curl_init($devUrl.$url[0]);

    curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_NOBODY, 1);

    $response = curl_exec($handle);

    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

     if($httpCode == 404) {
         echo $httpCode." ----> " . $url[0] . PHP_EOL;
     }

    curl_close($handle);
}