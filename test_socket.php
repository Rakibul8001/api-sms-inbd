<?php
$host = '36.255.68.114';
$port = 80;

$fp = fsockopen($host, $port, $errno, $errstr, 30);

if ($fp) {
    // Connection successful, perform further operations
    // For example, send and receive data using fwrite() and fgets()
    fwrite($fp, "GET / HTTP/1.1\r\nHost: $host\r\n\r\n");
    $response = fgets($fp);
    
    // Close the socket connection
    fclose($fp);
    
    // Handle the response or perform other operations
    echo $response;
} else {
    // Connection failed, handle the error
    echo "Connection error: $errstr ($errno)";
}
