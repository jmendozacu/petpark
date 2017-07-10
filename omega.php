<pre><?php
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://private-anon-b1f0433f2-inteo.apiary-proxy.com/api/v1/products/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  "Content-Type: application/json",
  "Authorization: Bearer b8536c6e-b875-4d4c-8977-35730699e9e5"
));

$response = curl_exec($ch);
curl_close($ch);

//var_dump($response);

$data = json_decode($response, true);

var_dump($data);