<html>
<head>
    <title>IOT Device Registry</title>
    <meta http-equiv="refresh" content="180">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <!-- favicon from https://www.flaticon.com/free-icons/networking -->
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <style>
    body, h1, h2, h3 {
        font-family: Arial, Helvetica, sans-serif;
    }
    table, th , td {
        border: 1px solid grey;
        border-collapse: collapse;
        padding: 8px;
        font-family: Arial, Helvetica, sans-serif;
    }
    th {
        background: darkblue;
        color: white;
        text-align: left;
    }
    table tr:nth-child(odd) {
        background-color: lightgray;
    }
    table tr:nth-child(even) {
        background-color: #FFFFFF;
    }
    .mono {
        font-family: 'Courier New', Courier, monospace;
    }
    .status {
        height: 32px;
        width: 32px;
    }
    .alert {
        height: 20px;
        width: 20px;
        position: relative;
        margin-left: -20px;
        margin-right: -20px;
        margin-top: -20px;
        z-index: 100;
    }
    .warning {
        color: red;
    }
    </style>
    <script>
    function explainStatus(event) {
        if (event.target.src.indexOf("icon-green") != -1) {
            alert ("This device has successfully checked in using the " + event.target.getAttribute("alt") + " script within the past hour.")
        }
        else if (event.target.src.indexOf("icon-yellow") != -1) {
            alert ("This device has not checked-in for more than an hour. The last successful check used the " + event.target.getAttribute("alt") + " script.")
        }
        else if (event.target.src.indexOf("icon-warning") != -1) {
            alert (event.target.getAttribute("alt"));
        }
    }
    function showLocalTime() {
        var timeStamps = document.getElementsByClassName("timeStamp");
        for (var i = 0; i < timeStamps.length; i++) {
            try {
                var timeStamp = timeStamps[i].innerText.replace(" UTC", "");
                var dt = tzShift(new Date(timeStamp));
                timeStamps[i].innerText = dt.toLocaleDateString('en-US') + " " + dt.toLocaleTimeString('en-US');
            } catch (ex) {
                //UTC will have to do!
            }
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
$warnText = "This device sent a malformed payload on its last check-in, it may be compromised or have a problem.";
$files = glob('../cache/*.{json}', GLOB_BRACE);
foreach($files as $file) {
    $data = json_decode(file_get_contents("../cache/" . $file));
    echoLine("<tr class=\"detailRow\">");
    echo("  <td><img class=\"status\" src=\"" . getIconForTimestamp($data->lastcheckin) . "\" onclick=\"explainStatus(event)\"");
    if (isset($data->version))
        echo(" alt=\"v" . $data->version . "\" title=\"v" . $data->version . "\">");
    else
        echo(">");
    if ($data->suspect)
        echo("<img class=\"alert\" src=\"icon-warning.png\" alt=\"$warnText\" title=\"$warnText\" onclick=\"explainStatus(event)\">");
    echoLine("</td>");
    echoLine("  <td class=\"mono\">" . $data->hostname . "</td>");
    echoLine("  <td>" . $data->wanip . "</td>");
    echoLine("  <td class=\"mono\">" . $data->iotid . "</td>");
    echoLine("  <td class=\"timestamp\" alt=\"$data->checkinServer\" title=\"$data->checkinServer\">" . $data->lastcheckin . " UTC</td>");
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
function getIconForTimestamp($timeStamp) {
    $nowTime = new \DateTime("now", new \DateTimeZone("UTC"));
    $checkinTime = date_create($timeStamp);
    $diffTime = date_diff($nowTime, $checkinTime);
    $diffHours = $diffTime->format('%h');
        $iconPath = "icon-green.png";
    if ($diffHours > 1)
        $iconPath = "icon-yellow.png";
    return $iconPath;
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