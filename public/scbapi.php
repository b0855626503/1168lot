<?php
$account = $_GET['acc'];
$url = 'https://scb.z7z.work/' . $account . '/getbalance';
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => array(
        'access-key:  53cb498c-8516-420f-bd65-90754e19bfbf',
    ),
));

$response = curl_exec($curl);
$api = json_decode($response, true);
curl_close($curl);
var_dump($response);
echo 'Last error: ', json_last_error(), PHP_EOL, PHP_EOL;
echo 'Last error msg: ', json_last_error_msg(), PHP_EOL, PHP_EOL;
