<?php

$cid = $_POST['cid'];
$score = $_POST['score'];


$service_url = "https://tcc353.encs.concordia.ca/api/index.php/contracts/{$cid}/score";
$curl = curl_init($service_url);
$curl_post_data = array(
    "cid" => $cid,
    "score" => $score
);
$headers = array(
    'Authorization: Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'])
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($httpcode == 200) {
    header("Location: ./clientHome.php");
} else{
    echo 'Error';
}
?>
