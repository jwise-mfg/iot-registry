<?php
header('Content-Type: text/plain; charset=utf-8');
if (isset($_GET["iotid"])) {
    $file = "../cache/" . $_GET["iotid"] . "ports.txt";
    if (file_exists($file)) {
        $data = file_get_contents($file);
        $data = "IOT ID: " . $_GET["iotid"] . PHP_EOL . PHP_EOL . $data;
        $data = str_replace('"', "", $data);
        $data = str_replace('\\n', PHP_EOL, $data);
        die($data);
    } else {
        die ("No ports file for IOT ID");
    }
} else {
    die ("No IOT ID to load");
}
?>