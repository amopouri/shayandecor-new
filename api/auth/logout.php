<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();
ok('با موفقیت خارج شدید.');
