<?php
$script = file_get_contents('./checkin', true);
$script = str_replace("<SERVERPATH>", ("https://" . $_SERVER['HTTP_HOST']), $script);
header('Content-type: text/plain; charset=utf-8');
header('Content-Disposition: filename="checkin"');
echo $script;
?>