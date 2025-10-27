# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

IOT Registry is a simple PHP-based registry system for tracking headless IOT devices. It consists of:
- **Server**: PHP web application that receives device check-ins and displays a dashboard
- **Client**: Bash script that runs on IOT devices to report their status

The architecture follows a client-reports-to-server pattern where devices periodically send their information (hostname, IP addresses, ports, etc.) to the server, which stores it in flat files and displays it in a web dashboard.

## Architecture

### Server Components

- **checkin/index.php**: API endpoint that receives device check-ins via GET parameters and POST body. Creates/updates JSON files in `cache/` directory with device information. Uses `common.php` for IP detection (including CloudFlare support).

- **view/index.php**: Main dashboard that reads all `cache/*.json` files and displays device registry as an HTML table. Auto-refreshes every 3 minutes. Shows device status with color-coded icons (green = checked in within 1 hour, yellow = older check-ins).

- **view/ports.php**: Displays network port information for a specific device identified by `iotid` parameter.

- **scripts/checkin/index.php**: Dynamic script delivery endpoint that serves the client bash script with `<SERVERPATH>` placeholder replaced by the server's actual hostname.

- **whoami/index.php**: Utility endpoint that returns the caller's IP address using `getUserIP()`.

- **cache/**: Directory storing device data as flat files (`{iotid}info.json` and `{iotid}ports.txt`). Gitignored.

### Client Script (scripts/checkin/checkin)

Bash script (v1.4) with three modes:
1. **Normal operation**: Collects device info (hostname, IPs, username, architecture, ports via netstat) and sends to server
2. **--install-service**: Installs as systemd service in `/opt/iot-registry/` with 30-minute check-in timer
3. **--remove-service**: Uninstalls systemd service
4. **--generate-id**: Creates or displays unique device ID stored in `~/.iotid` or `/opt/iot-registry/.iotid`

Device IDs are generated from MAC address + random 2-digit number for uniqueness.

### Data Flow

1. Client script collects device information and open ports
2. Device info sent as GET parameters to `/checkin/`
3. Port list sent as POST body (netstat -lntu output)
4. Server validates data (requires at least 3 data points), sanitizes inputs
5. Server stores `{iotid}info.json` with device metadata and `{iotid}ports.txt` with port information
6. Dashboard reads all JSON files from cache and renders table
7. Server marks entries as "suspect" if malformed payload detected

## Configuration

All security and operational settings are centralized in `config.php`:
- **Allowed hostnames**: Whitelist of domains to prevent Host header injection
- **Rate limiting**: Max requests per IP per time window
- **CloudFlare IPs**: Trusted proxy ranges for CF-Connecting-IP header
- **Trusted proxies**: Additional reverse proxy IPs for X-Forwarded-For
- **Input limits**: Maximum lengths for all user inputs

Edit `config.php` to add your production domain(s) to the `allowed_hosts` array before deployment.

## Development

### Testing Server Locally

This is a PHP application requiring PHP 7+. It assumes deployment at domain root (not subdirectory).

```bash
# Start PHP built-in server
php -S localhost:8000

# Access dashboard
open http://localhost:8000/view/
```

### Testing Client Script

The client script is designed to run on Linux IOT devices but can be tested locally:

```bash
# Download and run manually (replace with your server URL)
wget http://localhost:8000/scripts/checkin
chmod +x checkin
./checkin

# Or test ID generation
./checkin --generate-id
```

### Key Files to Modify

- **config.php**: Centralized configuration for hostnames, rate limits, proxy IPs, and input validation limits
- **checkin/index.php**: Modify to add new device data fields or change data validation logic
- **view/index.php**: Modify to change dashboard layout or add new columns
- **scripts/checkin/checkin**: Client script - update version number in `queryString` when making changes
- **common.php**: Shared IP detection logic used by multiple endpoints

## Security Considerations

This codebase is designed for test/demo environments. Production use requires additional security:

- Secure the `/cache/` and `/view/` directories with web server authentication
- All endpoints use HTTPS (client script checks for this)
- Server sanitizes inputs with `FILTER_SANITIZE_STRING` and `FILTER_VALIDATE_IP`
- Malformed payloads flagged as "suspect" in dashboard
- No script execution on server side - only data storage
- Client script has no sensitive credentials (server URL only)

## Notes

- The `<SERVERPATH>` placeholder in `scripts/checkin/checkin` gets replaced dynamically when downloaded via `/scripts/checkin/`
- Device status icons show green if checked in within 1 hour, yellow if older (see `getIconForTimestamp()` in view/index.php:139)
- The dashboard reminds users to secure the view folder if `PHP_AUTH_USER` is not detected
- Systemd service runs 30 minutes after boot, then every 30 minutes with 20-second random delay
