# Security Fixes Applied

This document describes the security vulnerabilities that were fixed and the measures implemented to protect the IOT Registry application.

## Summary

**Total vulnerabilities fixed:** 10 (Critical: 3, High: 3, Medium: 4)

All critical, high, and medium severity vulnerabilities have been addressed. The application is now significantly more secure against common web attacks.

---

## Critical Vulnerabilities Fixed

### 1. Path Traversal in `view/ports.php` (FIXED)

**Previous Issue:** The `iotid` parameter was used directly in file paths without validation, allowing attackers to read arbitrary files.

**Fix Applied:**
- Added regex validation to only allow alphanumeric characters, hyphens, and underscores
- Added length validation (max 64 characters)
- Implemented `realpath()` verification to ensure files are within the cache directory
- Added HTML escaping for output

**File:** `view/ports.php` (lines 11-27)

### 2. Path Traversal in `checkin/index.php` via Cookie (FIXED)

**Previous Issue:** IOT ID from cookies was not validated before being used in file paths.

**Fix Applied:**
- Created `validateIotId()` function that enforces strict format requirements
- All IOT IDs (from GET, POST, or cookies) are now validated before use
- Added multiple validation checkpoints before file operations

**File:** `checkin/index.php` (lines 19-30, 117-121)

### 3. Path Traversal in `checkin/index.php` via GET (FIXED)

**Previous Issue:** `FILTER_SANITIZE_STRING` does not prevent path traversal.

**Fix Applied:**
- Replaced `FILTER_SANITIZE_STRING` with custom `sanitizeString()` function
- Added `validateIotId()` validation for all IOT IDs
- Implemented `realpath()` checks before file writes

**File:** `checkin/index.php` (lines 98-104, 190-209)

---

## High Vulnerabilities Fixed

### 4. Stored XSS in `view/index.php` (FIXED)

**Previous Issue:** User-controlled data was echoed to HTML without escaping, allowing JavaScript injection.

**Fix Applied:**
- Added `htmlspecialchars()` with `ENT_QUOTES` and UTF-8 encoding to all user-controlled output
- Applied to: hostname, wanip, iotid, username, arch, lanips, version, checkinServer
- Added `urlencode()` for URL parameters

**File:** `view/index.php` (lines 109-138)

### 5. Host Header Injection in `scripts/checkin/index.php` (FIXED)

**Previous Issue:** `$_SERVER['HTTP_HOST']` was trusted without validation, allowing malicious content injection into downloaded scripts.

**Fix Applied:**
- Created `validateAndGetHost()` function with whitelist and format validation
- Added regex to validate proper hostname format
- Configured explicit whitelist of allowed hosts

**File:** `scripts/checkin/index.php` (lines 3-27)

### 6. Host Header Storage in `checkin/index.php` (FIXED)

**Previous Issue:** Host header was stored without validation.

**Fix Applied:**
- Created `validateHostname()` function with whitelist and format validation
- Hostname is now validated before storage
- Invalid hostnames are replaced with 'unknown'

**File:** `checkin/index.php` (lines 32-49, 86-88)

---

## Medium Vulnerabilities Fixed

### 7. IP Spoofing in `common.php` (FIXED)

**Previous Issue:** Proxy headers were trusted without verification, allowing IP address spoofing.

**Fix Applied:**
- Implemented CloudFlare IP range validation
- Only trust `HTTP_CF_CONNECTING_IP` if request originates from CloudFlare IP ranges
- Added `ip_in_range()` helper function for CIDR validation
- `X-Forwarded-For` only trusted from explicitly configured proxy IPs
- Default to `REMOTE_ADDR` for maximum security

**File:** `common.php` (lines 2-96)

**Note:** Update CloudFlare IP ranges periodically from https://www.cloudflare.com/ips/

### 8. Deprecated FILTER_SANITIZE_STRING (FIXED)

**Previous Issue:** Code used `FILTER_SANITIZE_STRING` which is deprecated in PHP 8.1+ and removed in PHP 9.0.

**Fix Applied:**
- Created `sanitizeString()` function as replacement
- Removes null bytes, trims input, enforces length limits
- Applied to all user inputs: iotid, version, hostname, username, arch
- More secure and future-proof

**File:** `checkin/index.php` (lines 4-17)

### 9. No Rate Limiting (FIXED)

**Previous Issue:** Check-in endpoint had no rate limiting, enabling disk exhaustion and DoS attacks.

**Fix Applied:**
- Implemented IP-based rate limiting with `checkRateLimit()` function
- Configured to allow 60 requests per minute per IP
- Rate limit data stored in cache directory
- Returns HTTP 429 (Too Many Requests) when limit exceeded
- Automatically cleans up old request data

**File:** `checkin/index.php` (lines 51-79, 93-94)

### 10. No CSRF Protection (MITIGATED)

**Previous Issue:** No CSRF tokens on endpoints.

**Mitigation Applied:**
- Created `csrf-helper.php` with complete CSRF token functions
- Documented that CSRF is not applicable to `/checkin/` endpoint (programmatic API)
- Rate limiting provides some protection against abuse
- CSRF helper ready for future admin functionality

**File:** `csrf-helper.php` (new file)

**Note:** The `/checkin/` endpoint is designed for bash script access, not browser-based forms, so traditional CSRF protection is not applicable. Future web-based admin features should use the CSRF helper.

---

## Additional Security Improvements

### Input Length Limits

Added maximum length validation for all inputs:
- `iotid`: 64 characters
- `version`: 32 characters
- `hostname`: 255 characters
- `username`: 64 characters
- `arch`: 32 characters
- POST body: 100,000 characters

### Secure Cookie Generation

Replaced `uniqid()` with `random_bytes()` for cryptographically secure random IDs:
```php
$newId = bin2hex(random_bytes(16));
```

Added secure cookie attributes:
- `httponly`: Prevents JavaScript access
- `samesite`: Prevents CSRF via cookies
- `secure`: Requires HTTPS

**File:** `checkin/index.php` (lines 108-115)

### Enhanced IP Validation

- All IPs validated with `FILTER_VALIDATE_IP`
- Empty IP lists now rejected
- Trimming applied before validation

---

## Configuration Required

All configuration is now centralized in `config.php`. Edit this file to customize for your environment:

### 1. Update Allowed Hostnames

**`config.php` (lines 17-23):**
```php
'allowed_hosts' => [
    'localhost',
    'localhost:8000',
    'your-iot-domain.com',  // Add your domain(s)
],
```

### 2. Configure Trusted Proxies (if using load balancers)

**`config.php` (lines 63-66):**
```php
'trusted_proxies' => [
    '10.0.0.1',  // Add your load balancer IPs
],
```

### 3. Update CloudFlare IP Ranges (if using CloudFlare)

**`config.php` (lines 39-54):**
Update the CloudFlare IP ranges periodically from:
https://www.cloudflare.com/ips/

### 4. Adjust Rate Limiting (optional)

**`config.php` (lines 29-32):**
```php
'rate_limit' => [
    'max_requests' => 60,      // Requests per time window
    'time_window' => 60,       // Time window in seconds
],
```

---

## Testing Recommendations

### Test Path Traversal Protection

```bash
# Should fail with "Invalid IOT ID format"
curl "http://localhost:8000/view/ports.php?iotid=../../../../etc/passwd"
curl "http://localhost:8000/view/ports.php?iotid=../secret"
```

### Test XSS Protection

```bash
# Create malicious check-in
curl "http://localhost:8000/checkin/?iotid=test123&hostname=<script>alert(1)</script>&username=admin&ips=1.1.1.1"

# View dashboard - script tags should be HTML-escaped
curl "http://localhost:8000/view/"
```

### Test Rate Limiting

```bash
# Send 61 requests rapidly - last one should return 429
for i in {1..61}; do
    curl "http://localhost:8000/checkin/?iotid=test$i&hostname=test&username=test&ips=1.1.1.1"
done
```

### Test Host Header Validation

```bash
# Should fail with "Invalid host header"
curl -H "Host: evil.com; malicious" "http://localhost:8000/scripts/checkin"
```

---

## Remaining Low-Risk Issues

These low-severity issues were not fixed but should be considered for future improvements:

1. **Predictable Cookie IDs:** Now fixed with `random_bytes()`
2. **Information Disclosure:** Error messages could be made more generic
3. **Directory Listing:** Ensure web server blocks directory browsing for `/cache/` and `/view/`

---

## Production Deployment Checklist

- [ ] Update all hostname whitelists in code
- [ ] Configure web server authentication for `/view/` directory
- [ ] Configure web server authentication for `/cache/` directory
- [ ] Ensure HTTPS is enforced (client script requires it)
- [ ] Add CloudFlare IP ranges if using CloudFlare
- [ ] Add trusted proxy IPs if using load balancers
- [ ] Set appropriate file permissions on cache directory (writable by web server only)
- [ ] Test all security fixes in staging environment
- [ ] Update `cache/.ratelimit_*` file permissions

---

## Questions or Issues?

If you discover any security vulnerabilities or have questions about these fixes, please create an issue in the repository.

**Important:** These fixes significantly improve security but do not eliminate all risks. This application is designed for test/demo environments. Additional hardening is required for production use.
