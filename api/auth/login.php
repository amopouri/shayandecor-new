<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
$in = input();
$user = s($in['username'] ?? '', 60);
$pass = (string)($in['password'] ?? '');
q('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
$cnt = (int)q('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND created_at > (NOW() - INTERVAL 15 MINUTE)', [client_ip()])->fetchColumn();
if ($cnt >= 5) fail('تعداد تلاش‌های ناموفق زیاد است. ۱۵ دقیقه بعد دوباره تلاش کنید.', 429);
$row = q('SELECT * FROM users WHERE username=? AND status=1', [$user])->fetch();
if (!$row || !password_verify($pass, $row['password'])) {
    q('INSERT INTO login_attempts (ip, username) VALUES (?,?)', [client_ip(), $user]);
    fail('نام کاربری یا رمز عبور اشتباه است.', 401);
}
if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
    q('UPDATE users SET password=? WHERE id=?', [password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
}
session_regenerate_id(true);
$_SESSION['uid'] = (int)$row['id'];
$_SESSION['last'] = time();
unset($_SESSION['csrf']);
q('DELETE FROM login_attempts WHERE ip=?', [client_ip()]);
ok('خوش آمدید', ['user' => ['id' => (int)$row['id'], 'name' => $row['name'], 'username' => $row['username']], 'csrf' => csrf_token()]);
