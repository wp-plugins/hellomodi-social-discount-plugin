<?php
require_once "nusoap/lib/nusoap.php";
$client = new nusoap_client("http://ojemo.com/soap-server/modiserver.php");
$error = $client->getError();
if ($error) {
    echo "<h2>Constructor error</h2><pre>" . $error . "</pre>";
}
$result = $client->call("get_checkout", array("session" => "sdfssssdf"));
if ($client->fault) {
    echo "<h2>Fault</h2><pre>";
    print_r($result);
    echo "</pre>";
}
else {
    $error = $client->getError();
    if ($error) {
        echo "<h2>Error</h2><pre>" . $error . "</pre>";
    }
    else {
        echo "<h2>Books</h2><pre>";
        echo $result;
        echo "</pre>";
    }
}
