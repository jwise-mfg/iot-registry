<?php
$content = file_get_contents('./README.md', true);
header('Content-type: text/markdown; charset=utf-8');
echo $content;
?>