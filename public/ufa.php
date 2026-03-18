<?php
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://scb.z7z.work/7192455032/getbalance',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => array(
        'access-key: 53cb498c-8516-420f-bd65-90754e19bfbf'
    ),
));

$response = curl_exec($curl);
function removeBOM($data) {
    if (0 === strpos(bin2hex($data), 'efbbbf')) {
        return substr($data, 3);
    }
    return $data;
}
curl_close($curl);
var_dump($response);
echo '<br>';
$api = removeBOM($response);
$json = json_decode($api,true);
var_dump($json);
echo '<br>Last error: ', json_last_error(), PHP_EOL, PHP_EOL;
echo '<br>Last error msg: ', json_last_error_msg(), PHP_EOL, PHP_EOL;

