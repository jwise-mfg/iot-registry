Devices will be listed here.

You might want to secure this folder.

<?php
$files = glob('../cache/*.{json}', GLOB_BRACE);
foreach($files as $file) {
    echo file_get_contents("../cache/" . $file);
}
?>