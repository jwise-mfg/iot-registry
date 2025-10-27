<?php
/**
 * CSRF Protection Helper Functions
 *
 * Note: CSRF protection is NOT applied to the /checkin/ endpoint as it's
 * designed for programmatic access from bash scripts, not web browsers.
 *
 * Use these functions for any future administrative endpoints that need
 * CSRF protection (e.g., deleting devices, changing settings, etc.)
 */

// Start session if not already started
function csrf_start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Generate a CSRF token
function csrf_generate_token() {
    csrf_start_session();

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Validate a CSRF token
function csrf_validate_token($token) {
    csrf_start_session();

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Get CSRF token from request
function csrf_get_token_from_request() {
    // Check POST parameter
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }

    // Check custom header
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    return null;
}

// Require valid CSRF token or die
function csrf_require_token() {
    $token = csrf_get_token_from_request();

    if (!$token || !csrf_validate_token($token)) {
        http_response_code(403);
        die("Invalid or missing CSRF token");
    }
}

// Generate HTML hidden input field with CSRF token
function csrf_token_field() {
    $token = csrf_generate_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// Get token for use in JavaScript/AJAX
function csrf_get_token_meta_tag() {
    $token = csrf_generate_token();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Example usage in a form:
 *
 * <form method="POST" action="delete_device.php">
 *     <?php echo csrf_token_field(); ?>
 *     <input type="hidden" name="device_id" value="123">
 *     <button type="submit">Delete Device</button>
 * </form>
 *
 * Then in delete_device.php:
 *
 * require_once('csrf-helper.php');
 * csrf_require_token();
 * // ... proceed with deletion
 */
?>
