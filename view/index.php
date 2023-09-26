<html>
<head>
    <title>IOT Device Registry</title>
    <style>
        body, h1, h2, h3 {
            font-family: Arial, Helvetica, sans-serif;
        }
        /*Style for Table*/
        table, th , td {
            border: 1px solid grey;
            border-collapse: collapse;
            padding: 8px;
            font-family: Arial, Helvetica, sans-serif;
        }
        /*Style for Table Header*/
        th {
            background: darkblue;
            color: white;
            text-align: left;
        }
        /*Style for Alternate Rows*/
        table tr:nth-child(odd) {
            background-color: lightgray;
        }
        table tr:nth-child(even) {
            background-color: #FFFFFF;
        }
        .status {
            height: 32px;
            width: 32px;
        }
        .warning {
            color: red;
        }
    </style>
    <script>
        function showLocalTime() {
            var timeStamps = document.getElementsByClassName("timeStamp");
            for (var i = 0; i < timeStamps.length; i++) {
                var timeStamp = timeStamps[i].innerText.replace(" UTC", "");
                var dt = tzShift(new Date(timeStamp));
                timeStamps[i].innerText = dt.toLocaleDateString('en-US') + " " + dt.toLocaleTimeString('en-US');
            }
        }
        function tzShift(dt) {
            now = new Date();
            var diff = now.getHours() - now.getUTCHours();
            var dtShifted = dt.addHours(diff);
            return dtShifted
        }
        Date.prototype.addHours = function(h){
            this.setHours(this.getHours()+h);
            return this;
        }
    </script>
</head>
<body onload="showLocalTime()">
<h2>IOT Device Registry</h2>

<table>
<tr class="titleRow">
   <th>&nbsp;</th>
   <th>Host Name</th>
   <th>WAN IP</th>
   <th>IOT ID</th>
   <th>Last Check-in</th>
   <th>Last Username</th>
   <th>Architecture</th>
   <th>LAN IPs</th>
   <th>Ports</th>
</tr>
<?php
$okText = "This device sent a valid payload on its last check-in";
$warnText = "This device sent a malformed payload on its last check-in, it may be compromised or have a problem.";
$files = glob('../cache/*.{json}', GLOB_BRACE);
foreach($files as $file) {
    $data = json_decode(file_get_contents("../cache/" . $file));
    echoLine("<tr class=\"detailRow\">");
    if ($data->suspect)
        echoLine("  <td><img class=\"status\" src=\"icon-warning.png\" alt=\"$warnText\" title=\"$warnText\"></td>");
    else
        echoLine("  <td><img class=\"status\" src=\"icon-green.png\" alt=\"$okText\" title=\"$okText\"></td>");
    echoLine("  <td>" . $data->hostname . "</td>");
    echoLine("  <td>" . $data->wanip . "</td>");
    echoLine("  <td>" . $data->iotid . "</td>");
    echoLine("  <td class=\"timestamp\">" . $data->lastcheckin . " UTC</td>");
    echoLine("  <td>" . $data->username . "</td>");
    echoLine("  <td>" . $data->arch . "</td>");
    if (is_array($data->lanips)) {
        echoLine("  <td>");
        foreach($data->lanips as $ip) {
            echoLine($ip . "<br>");
        }
        echoLine("  </td>");
    } else {
        echoLine("  <td>" . $data->lanips . "</td>");
    }
    $portsFile = str_replace("info.json", "ports.txt", $file);
    if (file_exists("../cache/" . $portsFile))
        echoLine("  <td> <a href=\"ports.php?iotid=$data->iotid\" target=\"ports\">View Ports</a> </td>");
    echoLine("</tr>");
}
function echoLine($line) {
    echo $line . "\r\n";
}
?>
</table>
<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    echo "<p class=\"warning\"><i>Don't forget to secure this folder!</i></p>";
}
?>
</body>
<html>