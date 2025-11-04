<?php
require_once __DIR__ . '/User.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_current_user_data(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function is_logged_in(): bool
{
    return get_current_user_data() !== null;
}

function is_admin(): bool
{
    $user = get_current_user_data();
    return $user && $user['role'] === 'admin';
}

function login_user(array $user): void
{
    $_SESSION['auth_user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'country_id' => $user['country_id'] ?? null,
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        header('Location: ../dashboard.php');
        exit;
    }
}
