<?php
include ("../common.php");
$postBody=file_get_contents('php://input');

$devInfo = new stdClass();
$devInfo->wanip = getUserIP();
$devInfo->checkinServer = $_SERVER['HTTP_HOST'];
$devInfo->lastcheckin = date('m/d/Y h:i:s a', time());
$devInfo->suspect = false;

// Get Device Info
$dataScore = 0;
if (isset($_GET["iotid"])) {
   $devInfo->iotid = filter_var($_GET["iotid"], FILTER_SANITIZE_STRING);
   $dataScore++;
}
else {
   if(!isset($_COOKIE['iotid'])) {
      $newId = uniqid();
      setcookie('iotid', $newId);
      $_COOKIE['iotid'] = $newId;
  }
  $devInfo->iotid = $_COOKIE['iotid'];
}
if (isset($_GET["version"])) {
   $devInfo->version = filter_var($_GET["version"], FILTER_SANITIZE_STRING);
}
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
      $thisIp = filter_var($_GET["ips"], FILTER_VALIDATE_IP);
      if ($thisIp) {
         $devInfo->lanips = $thisIp;
      }
   }
   $dataScore++;
}
if ($dataScore < 3)
   die("Insufficient Data");

// Check port payload
$ports = "";
if (isset($postBody) && $postBody != "") {
   if (strpos($postBody, "Active Internet connections") === 0) {
      foreach(preg_split("/((\r?\n)|(\r\n?))/", $postBody) as $line){
         $ports = $ports . filter_var($line, FILTER_SANITIZE_STRING) . PHP_EOL;
      }
   } else {
      $devInfo->suspect = true;
   }
}

// Save check-in to cache
$filenameBase = "../cache/".$devInfo->iotid;
file_put_contents ($filenameBase . "info.json", json_encode($devInfo));
if ($ports != "")
   file_put_contents ($filenameBase . "ports.txt", json_encode($ports));
?>
OK
