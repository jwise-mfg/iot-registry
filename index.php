<?php
$content = file_get_contents('./README.md', true);
header('Content-type: text/plain; charset=utf-8');
echo $content;
?>