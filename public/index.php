<?php

header("Access-Control-Allow-Origin: *");   
header("Content-Type: application/json; charset=UTF-8");    
header("Access-Control-Allow-Methods: POST, OPTIONS");    
header("Access-Control-Max-Age: 3600");    
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-TOKEN");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {    
   return 0;
}   

date_default_timezone_set('Asia/Dhaka');

error_reporting(E_ALL);
ini_set('display_errors', 0);


$ip = isset($_SERVER['HTTP_CLIENT_IP']) 
    ? $_SERVER['HTTP_CLIENT_IP'] 
    : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) 
      ? $_SERVER['HTTP_X_FORWARDED_FOR'] 
      : $_SERVER['REMOTE_ADDR']);
      
//ip blocking
// $blockedIp = array('162.213.209.242');
// if(in_array($ip, $blockedIp)){
    
//     // $apiToken = isset($_POST["api_token"])? $_POST["api_token"] :  $_GET['api_token'] ;

//     // if(empty($apiToken)){
//     //     exit;
//     // }
    
//     // $data = sprintf(
//     //   "%s \t %s \t %s \t %s \t %s \t",
//     //   $_SERVER['REQUEST_METHOD'],
//     //   $_SERVER['REQUEST_URI'] . "Blocked.......",
//     //   $apiToken,
//     //   isset($_POST["message"])? $_POST["message"] : 'No Message/Not Message API',
//     //   $ip
//     // );
//     // $data .= " ".isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER'] : 'NO-REFERRER';
//     // $data.= " \n";
    
//     // file_put_contents(
//     //   'requestsApiLogs.csv',
//     //   $data, FILE_APPEND
//     // );
//     exit;
// }

$apiToken = isset($_POST["api_token"])? $_POST["api_token"] :  $_GET['api_token'] ;

if(empty($apiToken)){
    echo "No token Found!";
    exit;
}

$message = '';
if(isset($_POST["message"]) && !empty($_POST["message"])){
    $message = $_POST["message"];
} else if(isset($_GET["message"]) && !empty($_GET["message"])) {
    $message = $_GET["message"];
} else {
    $message = 'No Message/Not Message API';
}

$data = sprintf(
   "%s \t %s \t %s \t %s \t %s ",
   $_SERVER['REQUEST_METHOD'],
   explode('?', $_SERVER['REQUEST_URI'])[0],
   $apiToken,
   $message,
   $ip
);

$data.= " \n";

file_put_contents(
   'request_logs/requestsApiLogs_'.date(ymd).'.csv',
   $data, FILE_APPEND
);

require __DIR__ . "/../bootstrap/app.php";



$app->run();
