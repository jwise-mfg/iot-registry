<?php
$script = file_get_contents('./check.sh', true);
$script = str_replace("<SERVERPATH>", "https://thisisatest", $script);
header('Content-type: text/plain; charset=utf-8');
echo $script;
?>