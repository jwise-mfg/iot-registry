<?php
$postBody=file_get_contents('php://input');

$devInfo = new stdClass();
$devInfo->wanip = getUserIP();
$devInfo->lastcheckin = date('m/d/Y h:i:s a', time());
$dataScore = 0;
if (isset($_GET["iotid"])) {
   $devInfo->iotid = filter_var($_GET["iotid"], FILTER_SANITIZE_STRING);
   $dataScore++;
}
else
   $devInfo->iotid = uniqid();
if (isset($_GET["hostname"])) {
   $devInfo->hostname = filter_var($_GET["hostname"], FILTER_SANITIZE_STRING);
   $dataScore++;
}
if (isset($_GET["username"])) {
   $devInfo->username = filter_var($_GET["username"], FILTER_SANITIZE_STRING);
   $dataScore++;
}
if (isset($_GET["arch"])) {
   $devInfo->arch = filter_var($_GET["arch"], FILTER_SANITIZE_STRING);
}
if (isset($_GET["ips"])) {
   $ips = $_GET["ips"];
   if (strpos($ips, ",") > -1) { //List of IPs
      $ipList = explode($ips, ",");
      $devInfo->lanips = [];
      foreach ($ipList as $thisIp) {
         $thisIp = filter_var($thisIp, FILTER_VALIDATE_IP);
         if ($thisIp) {
            array_push($devInfo->lanips, $thisIp);
         }
      }
   } else { //Just one IP
      $thisIp = filter_var($ip, FILTER_VALIDATE_IP);
      if ($thisIp) {
         $devInfo->lanips = $ips;
      }
   }
   $dataScore++;
}
if ($dataScore < 3)
   die("Insufficient Data");
$ports = "";
if (isset($postBody) && strpos($postBody, "Active Internet connections") != -1) {
   foreach(preg_split("/((\r?\n)|(\r\n?))/", $postBody) as $line){
      $ports = $ports . filter_var($line, FILTER_SANITIZE_STRING) . PHP_EOL;
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
