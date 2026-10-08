<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = input();
$d = content_def();
$id = n($in['id'] ?? 0);
$vals = [];
foreach ($d['cols'] as $col => $rule) {
    if (!array_key_exists($col, $in)) continue;
    $p = explode(':', $rule);
    switch ($p[0]) {
        case 'str':  $vals[$col] = s($in[$col], (int)$p[1]); break;
        case 'int':  $vals[$col] = n(from_persian_digits((string)$in[$col]), (int)$p[1], (int)$p[2]); break;
        case 'bool': $vals[$col] = b($in[$col]); break;
        case 'img':  $v = s($in[$col], 500); if (!valid_image_ref($v)) fail('مسیر تصویر نامعتبر است.', 422); $vals[$col] = $v; break;
        case 'enum': $v = s($in[$col], 30); if (!in_array($v, explode(',', $p[1]), true)) fail('مقدار نامعتبر است.', 422); $vals[$col] = $v; break;
    }
}
foreach ($d['required'] as $r) if (!$id ? (($vals[$r] ?? '') === '') : (array_key_exists($r, $vals) && $vals[$r] === '')) fail('لطفاً فیلدهای الزامی را پر کنید.', 422);
if ($d['table'] === 'blog_posts' && empty($vals['slug']) && !empty($vals['title'])) $vals['slug'] = make_slug($vals['title']);
if ($id) {
    $old = q("SELECT * FROM {$d['table']} WHERE id=?", [$id])->fetch();
    if (!$old) fail('مورد پیدا نشد.', 404);
    if ($vals) q("UPDATE {$d['table']} SET " . implode(',', array_map(fn($k) => "$k=?", array_keys($vals))) . ' WHERE id=?', [...array_values($vals), $id]);
    if (isset($vals['image']) && ($old['image'] ?? '') !== $vals['image']) delete_upload($old['image'] ?? '');
} else {
    if (!$vals) fail('داده‌ای ارسال نشد.', 422);
    q("INSERT INTO {$d['table']} (" . implode(',', array_keys($vals)) . ') VALUES (' . implode(',', array_fill(0, count($vals), '?')) . ')', array_values($vals));
    $id = (int)db()->lastInsertId();
}
ok('ذخیره شد.', ['id' => $id]);
