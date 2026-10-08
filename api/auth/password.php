<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
$u = require_admin();
$in = input();
$cur = (string)($in['current'] ?? ''); $new = (string)($in['new'] ?? ''); $rep = (string)($in['repeat'] ?? '');
$row = q('SELECT password FROM users WHERE id=?', [$u['id']])->fetch();
if (!$row || !password_verify($cur, $row['password'])) fail('رمز فعلی اشتباه است.', 422);
if (mb_strlen($new) < 8) fail('رمز جدید باید حداقل ۸ نویسه باشد.', 422);
if ($new !== $rep) fail('تکرار رمز جدید مطابقت ندارد.', 422);
if ($new === $cur) fail('رمز جدید باید با رمز فعلی متفاوت باشد.', 422);
q('UPDATE users SET password=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
session_regenerate_id(true);
ok('رمز عبور با موفقیت تغییر کرد.');
