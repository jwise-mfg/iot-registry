<?php
$postBody=file_get_contents('php://input');

$devInfo = new stdClass();
$devInfo->wanip = getUserIP();
if (isset($_GET["iotid"]))
   $devInfo->iotid = $_GET["iotid"];
else
   $devInfo->iotid = uniqid();
if (isset($_GET["hostname"]))
   $devInfo->hostname = $_GET["hostname"];
if (isset($_GET["username"]))
   $devInfo->username = $_GET["username"];
if (isset($_GET["arch"]))
   $devInfo->arch = $_GET["arch"];
if (isset($_GET["ips"])) {
   $ips = $_GET["ips"];
   if (strpos($ips, ",") > -1) {
      $ipList = explode($ips, ",");
      $devInfo->lanips = $ipList;
   } else {
      $devInfo->lanips = $ips;
   }
}
$ports = "";
if (isset($postBody)) {
   foreach(preg_split("/((\r?\n)|(\r\n?))/", $postBody) as $line){
      $ports = $ports . $line . PHP_EOL;
   }
}

$filenameBase = "../cache/".$devInfo->iotid;
file_put_contents ($filenameBase . "info.json", json_encode($devInfo));
file_put_contents ($filenameBase . "ports.txt", json_encode($ports));

function getUserIP()
{
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
              $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
              $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }
    return $ip;
}
?>
OK
