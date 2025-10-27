<?php
// Load configuration
$config = require_once("../config.php");
include ("../common.php");

// Helper function to sanitize string input (replaces deprecated FILTER_SANITIZE_STRING)
function sanitizeString($input, $maxLength = 255) {
    if (!is_string($input)) {
        return '';
    }
    // Remove null bytes and trim
    $input = str_replace("\0", '', $input);
    $input = trim($input);
    // Limit length
    if (strlen($input) > $maxLength) {
        $input = substr($input, 0, $maxLength);
    }
    return $input;
}

// Helper function to validate iotid format
function validateIotId($iotid, $config) {
    // Only allow alphanumeric characters and basic separators
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $iotid)) {
        return false;
    }
    // Length check using config
    $minLength = $config['input_limits']['iotid_min'];
    $maxLength = $config['input_limits']['iotid_max'];
    if (strlen($iotid) < $minLength || strlen($iotid) > $maxLength) {
        return false;
    }
    return true;
}

// Helper function to validate hostname
function validateHostname($hostname, $config) {
    $allowed_hosts = $config['allowed_hosts'];

    // If not in whitelist, validate format
    if (!in_array($hostname, $allowed_hosts)) {
        // Basic format validation - allow subdomains
        if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $hostname)) {
            return 'unknown';
        }
    }
    return $hostname;
}

// Rate limiting: simple IP-based rate limiting
function checkRateLimit($ip, $config) {
    $rateLimitFile = "../cache/.ratelimit_" . md5($ip);
    $maxRequests = $config['rate_limit']['max_requests'];
    $timeWindow = $config['rate_limit']['time_window'];

    $now = time();
    $requests = [];

    if (file_exists($rateLimitFile)) {
        $data = file_get_contents($rateLimitFile);
        $requests = json_decode($data, true) ?: [];
    }

    // Remove old requests outside time window
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });

    // Check if rate limit exceeded
    if (count($requests) >= $maxRequests) {
        http_response_code(429);
        die("Rate limit exceeded. Please try again later.");
    }

    // Add current request
    $requests[] = $now;
    file_put_contents($rateLimitFile, json_encode($requests));
}

$postBody = file_get_contents('php://input');

$devInfo = new stdClass();
$devInfo->wanip = getUserIP();

// Validate and sanitize host header
$rawHost = $_SERVER['HTTP_HOST'] ?? 'unknown';
$devInfo->checkinServer = validateHostname(sanitizeString($rawHost, $config['input_limits']['hostname_max']), $config);

$devInfo->lastcheckin = date('m/d/Y h:i:s a', time());
$devInfo->suspect = false;

// Rate limiting
checkRateLimit($devInfo->wanip, $config);

// Get Device Info
$dataScore = 0;
if (isset($_GET["iotid"])) {
   $iotid = sanitizeString($_GET["iotid"], $config['input_limits']['iotid_max']);
   if (!validateIotId($iotid, $config)) {
       die("Invalid IOT ID format");
   }
   $devInfo->iotid = $iotid;
   $dataScore++;
}
else {
   if(!isset($_COOKIE['iotid'])) {
      // Use more secure random ID generation
      $newId = bin2hex(random_bytes(16));
      setcookie('iotid', $newId, [
          'httponly' => true,
          'samesite' => 'Strict',
          'secure' => true
      ]);
      $_COOKIE['iotid'] = $newId;
  }
  $cookieId = sanitizeString($_COOKIE['iotid'], $config['input_limits']['iotid_max']);
  if (!validateIotId($cookieId, $config)) {
      die("Invalid IOT ID in cookie");
  }
  $devInfo->iotid = $cookieId;
}

if (isset($_GET["version"])) {
   $devInfo->version = sanitizeString($_GET["version"], $config['input_limits']['version_max']);
}

if (isset($_GET["hostname"])) {
   $devInfo->hostname = sanitizeString($_GET["hostname"], $config['input_limits']['hostname_max']);
   $dataScore++;
}

if (isset($_GET["username"])) {
   $devInfo->username = sanitizeString($_GET["username"], $config['input_limits']['username_max']);
   $dataScore++;
}

if (isset($_GET["arch"])) {
   $devInfo->arch = sanitizeString($_GET["arch"], $config['input_limits']['arch_max']);
}

if (isset($_GET["ips"])) {
   $ips = $_GET["ips"];
   if (strpos($ips, ",") !== false) { //List of IPs
      $ipList = explode(",", $ips);
      $devInfo->lanips = [];
      foreach ($ipList as $thisIp) {
         $thisIp = filter_var(trim($thisIp), FILTER_VALIDATE_IP);
         if ($thisIp) {
            array_push($devInfo->lanips, $thisIp);
         }
      }
      // Require at least one valid IP
      if (empty($devInfo->lanips)) {
          die("No valid IP addresses provided");
      }
   } else { //Just one IP
      $thisIp = filter_var(trim($_GET["ips"]), FILTER_VALIDATE_IP);
      if ($thisIp) {
         $devInfo->lanips = $thisIp;
      } else {
          die("Invalid IP address format");
      }
   }
   $dataScore++;
}

if ($dataScore < 3)
   die("Insufficient Data");

// Check port payload
$ports = "";
if (isset($postBody) && $postBody != "") {
   // Limit post body size
   if (strlen($postBody) > $config['input_limits']['post_body_max']) {
       die("Payload too large");
   }

   if (strpos($postBody, "Active Internet connections") === 0) {
      foreach(preg_split("/((\r?\n)|(\r\n?))/", $postBody) as $line){
         // Sanitize each line
         $cleanLine = sanitizeString($line, 1000);
         $ports = $ports . $cleanLine . PHP_EOL;
      }
   } else {
      $devInfo->suspect = true;
   }
}

// Validate iotid one more time before file operations
if (!validateIotId($devInfo->iotid, $config)) {
    die("Invalid IOT ID");
}

// Save check-in to cache with safe file paths
$cacheDir = realpath("../cache/");
if ($cacheDir === false) {
    die("Cache directory not found");
}

$filenameBase = $cacheDir . "/" . $devInfo->iotid;

// Additional safety check
$infoFile = $filenameBase . "info.json";
$portsFile = $filenameBase . "ports.txt";

if (strpos(realpath(dirname($infoFile)), $cacheDir) !== 0) {
    die("Invalid file path");
}

file_put_contents($infoFile, json_encode($devInfo));
if ($ports != "")
   file_put_contents($portsFile, json_encode($ports));
?>
OK
