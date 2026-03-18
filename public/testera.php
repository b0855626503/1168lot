<?php
header("Content-Type: application/json; charset=UTF-8");
$remote_key_url = array(
//    "http://163.44.196.203/node/remote.php?key=iamkey",
//    "http://116.204.182.175/node/remote.php?key=iamkey",
//    "http://110.78.208.32/node/remote.php?key=iamkey",
//    "http://110.78.208.13/node/remote.php?key=iamkey",
//    "http://202.139.192.137/node/remote.php?key=iamkey",
//    "http://43.229.149.32/node/remote.php?key=iamkey",
//    "http://95.111.197.36/node/remote.php?key=iamkey",
//    "http://116.204.182.178/node/remote.php?key=iamkey",
    "https://anachak.me/ui/index.php?key=iamkey",
    "https://th-vpn.in.net/3ird.onilne/3cb69245833dbcac0b9e7aef7931bce08c6be8132c88986fe79a22ecbd90761e/remote_key.php"
);

$url = $remote_key_url[array_rand($remote_key_url)];
$data = json_decode(file_get_contents($url),true);
$data['server'] = $url;
echo json_encode($data);
