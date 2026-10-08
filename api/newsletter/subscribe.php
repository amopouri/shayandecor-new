<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
$email = mb_strtolower(s(input()['email'] ?? '', 190));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('ایمیل واردشده معتبر نیست.', 422);
$recent = (int)q('SELECT COUNT(*) FROM subscribers WHERE ip=? AND created_at > (NOW() - INTERVAL 1 HOUR)', [client_ip()])->fetchColumn();
if ($recent >= 5) fail('تعداد درخواست‌ها زیاد است. کمی بعد تلاش کنید.', 429);
if (q('SELECT 1 FROM subscribers WHERE email=?', [$email])->fetch()) ok('این ایمیل قبلاً عضو شده است.');
q('INSERT INTO subscribers (email, ip) VALUES (?,?)', [$email, client_ip()]);
ok('عضویت شما با موفقیت ثبت شد.');
