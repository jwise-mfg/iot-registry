<?php
header('Content-Type: text/plain; charset=utf-8');

// Validate iotid parameter
if (!isset($_GET["iotid"])) {
    die("No IOT ID to load");
}

$iotid = $_GET["iotid"];

// Prevent path traversal: only allow alphanumeric characters and basic separators
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $iotid)) {
    die("Invalid IOT ID format");
}

// Additional length check
if (strlen($iotid) > 64) {
    die("IOT ID too long");
}

$file = "../cache/" . $iotid . "ports.txt";

// Use realpath to prevent path traversal and verify file is in cache directory
$realFile = realpath($file);
$cacheDir = realpath("../cache/");

if ($realFile === false || strpos($realFile, $cacheDir) !== 0) {
    die("Invalid file path");
}

if (file_exists($realFile)) {
    $data = file_get_contents($realFile);
    $data = "IOT ID: " . htmlspecialchars($iotid, ENT_QUOTES, 'UTF-8') . PHP_EOL . PHP_EOL . $data;
    $data = str_replace('"', "", $data);
    $data = str_replace('\\n', PHP_EOL, $data);
    die($data);
} else {
    die("No ports file for IOT ID");
}
?>