<?php
if (file_exists("home.html")) {
    $content = file_get_contents('./README.md', true);
    header('Content-type: text/html; charset=utf-8');
    echo $content;
} else {
    $content = file_get_contents('./README.md', true);
    header('Content-type: text/plain; charset=utf-8');
    echo $content;    
}
?>