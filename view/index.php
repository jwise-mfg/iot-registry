<p>Devices will be listed here.</p>

<p>You might want to secure this folder.</p>

<?php
$files = glob('../cache/*.{json}', GLOB_BRACE);
foreach($files as $file) {
    echo "<p>" . file_get_contents("../cache/" . $file) . "<br></p>";
}
?>