<?php

session_start();
date_default_timezone_set('Asia/Bangkok');
$session_id = session_id();
define('PASSKEY', 'd6as86d67-c67s84-4s8c9-88s889-3e36bs845e1s89');
$url = "http://manage.cluba8.com/api_service/create-check-account";
$ch = curl_init($url);
$data = array(
    'accountStatus' => 1,
    'accountType' => 1,
    'agentLoginName' => 'testzero99',
    'balance' => 0,
    'birthDate' => '1987-04-13',
    'email' => 'email@email.com',
    'firstName' => 'test',
    'gender' => 'G',
    'lastName' => 'test1',
    'loginName' => 'testzero99user',
    'mode' => 'real',
    'password' => 'test1234567',
    'timeStamp' => '2019-10-31T13:12:16'
);
echo "<pre>";
echo "<h3>Create User Request</h3>";
print_r($data);
echo "</pre>";
$postString = "";
foreach ($data as $keyR => $value) {
    $postString .= $keyR . '=' . $value . '&';
}
$postString = substr($postString, 0, -1);
$hashKey = md5($postString);
$payload = json_encode($data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$headers2 = array('Content-Type: application/json', 'Pass-Key: ' . PASSKEY, 'Session-Id: ' . $session_id, 'Hash: ' . $hashKey);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);
echo "<pre>";
echo "<h3>Create User Response</h3>";
print_r($result);
