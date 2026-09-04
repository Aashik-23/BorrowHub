<?php
/**
 * functions.php — shared helper functions for BorrowHub.
 * Session is started here so every page that includes this
 * file (directly or via header.php) has access to $_SESSION.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Trim + htmlspecialchars a piece of user input before display/storage. */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/** Is a user currently logged in? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Redirect to login.php if not logged in — call at the top of protected pages. */
function require_login(string $redirectTo = '../auth/login.php'): void
{
    if (!is_logged_in()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

/** Send the browser to a new URL and stop execution. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Store a one-time flash message in the session. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve (and clear) the current flash message, if any. */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Render the flash message as Bootstrap alert HTML, if one is queued. */
function render_flash(): void
{
    $flash = get_flash();
    if (!$flash) return;
    $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
    echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
        . '</div>';
}
