<?php
/**
 * Return URL after login / signup — keeps users on the page they visited.
 */

function auth_redirect_map(): array
{
    return [
        'subscription'            => '/Nepal-Travel/Public/subscription.php',
        'experience-subscription'   => '/Nepal-Travel/Public/experience-subscription.php',
        'event-subscription'      => '/Nepal-Travel/Public/event-subscription.php',
        'events'                  => '/Nepal-Travel/Public/events.php',
        'booking'                 => '/Nepal-Travel/Public/booking.php',
        'experience'              => '/Nepal-Travel/Public/experience.php',
    ];
}

function auth_default_redirect(): string
{
    return '/Nepal-Travel/Public/index.php';
}

function auth_sanitize_redirect(?string $raw): string
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return auth_default_redirect();
    }

    $map = auth_redirect_map();
    if (isset($map[$raw])) {
        return $map[$raw];
    }

    if (preg_match('#^/Nepal-Travel/[a-zA-Z0-9_./?=&%-]*$#', $raw) && !str_contains($raw, '..')) {
        return $raw;
    }

    if (preg_match('#^[a-zA-Z0-9_./?=&%-]+$#', $raw) && !str_contains($raw, '..')) {
        if (str_starts_with($raw, 'Public/')) {
            return '/Nepal-Travel/' . $raw;
        }
        return '/Nepal-Travel/Public/' . ltrim($raw, '/');
    }

    return auth_default_redirect();
}

function auth_current_request_uri(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? auth_default_redirect();
    if (preg_match('#/user/(login|Register|register|forgot_password|verify_email)#i', $uri)) {
        return auth_default_redirect();
    }
    return $uri;
}

/** Store redirect from ?redirect= or same-site referer when landing on login/register. */
function auth_capture_redirect(): void
{
    if (!empty($_GET['redirect'])) {
        $_SESSION['auth_redirect'] = auth_sanitize_redirect($_GET['redirect']);
        return;
    }

    if (!empty($_SESSION['auth_redirect'])) {
        return;
    }

    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && str_contains($ref, '/Nepal-Travel/')) {
        $path  = parse_url($ref, PHP_URL_PATH) ?: '';
        $query = parse_url($ref, PHP_URL_QUERY);
        if ($path && str_starts_with($path, '/Nepal-Travel/')
            && !preg_match('#/user/(login|Register|register|forgot_password)#i', $path)) {
            $_SESSION['auth_redirect'] = auth_sanitize_redirect($path . ($query ? '?' . $query : ''));
        }
    }
}

function auth_get_redirect_url(): string
{
    return auth_sanitize_redirect($_SESSION['auth_redirect'] ?? auth_default_redirect());
}

function auth_build_login_url(?string $redirect = null): string
{
    $target = auth_sanitize_redirect($redirect ?? auth_current_request_uri());
    return '/Nepal-Travel/user/login.php?redirect=' . urlencode($target);
}

function auth_build_register_url(?string $redirect = null): string
{
    $target = auth_sanitize_redirect($redirect ?? auth_current_request_uri());
    return '/Nepal-Travel/user/Register.php?redirect=' . urlencode($target);
}

function auth_redirect_after_login(string $role = 'user'): void
{
    if ($role === 'admin') {
        unset($_SESSION['auth_redirect']);
        header('Location: /Nepal-Travel/admin/dashboard.php');
        exit;
    }

    $url = auth_get_redirect_url();
    unset($_SESSION['auth_redirect']);
    header('Location: ' . $url);
    exit;
}
