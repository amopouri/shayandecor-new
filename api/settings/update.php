<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = (array)(input()['settings'] ?? []);
$cur = settings_all();
$save = [];
foreach (ALL_SETTINGS as $k) {
    if (!array_key_exists($k, $in)) continue;
    $v = $k === 'about_text' || $k === 'hero_subtitle' ? s($in[$k], 1500) : s($in[$k], 300);
    if ($k === 'primary_color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) fail('رنگ اصلی نامعتبر است.', 422);
    if ($k === 'default_theme' && !in_array($v, ['dark', 'light'], true)) fail('تم نامعتبر است.', 422);
    if ($k === 'maintenance') $v = (string)b($in[$k]);
    if ($k === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) fail('ایمیل نامعتبر است.', 422);
    if ($k === 'phone_raw') $v = preg_replace('/[^\d+]/', '', from_persian_digits($v)) ?? '';
    if ($k === 'whatsapp') $v = preg_replace('/\D/', '', from_persian_digits($v)) ?? '';
    if ($k === 'instagram') $v = preg_replace('#^(https?://)?(www\.)?instagram\.com/#i', '', ltrim($v, '@'));
    if ($k === 'logo' && !valid_image_ref($v)) fail('مسیر لوگو نامعتبر است.', 422);
    $save[$k] = $v;
}
foreach ($save as $k => $v) q('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, $v]);
if (isset($save['logo']) && ($cur['logo'] ?? '') !== $save['logo']) delete_upload($cur['logo'] ?? '');
ok('تنظیمات ذخیره شد.');
