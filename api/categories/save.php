<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = input();
$id = n($in['id'] ?? 0);
$name = s($in['name'] ?? '', 120);
if ($name === '') fail('نام دسته الزامی است.', 422);
$img = s($in['image'] ?? '', 500);
if (!valid_image_ref($img)) fail('مسیر تصویر نامعتبر است.', 422);
$slugIn = s($in['slug'] ?? '', 120);
$mc = ($in['models_count'] ?? '') === '' ? null : n(from_persian_digits((string)$in['models_count']));
$f = ['name' => $name, 'description' => s($in['description'] ?? '', 1000), 'image' => $img === '' ? null : $img, 'models_count' => $mc,
      'sort_order' => n($in['sort_order'] ?? 0, 0, 9999), 'is_active' => b($in['is_active'] ?? 1)];
if ($id) {
    $old = q('SELECT image FROM categories WHERE id=?', [$id])->fetch();
    if (!$old) fail('دسته پیدا نشد.', 404);
    $f['slug'] = unique_slug('categories', make_slug($slugIn !== '' ? $slugIn : $name), $id);
    q('UPDATE categories SET ' . implode(',', array_map(fn($k) => "$k=?", array_keys($f))) . ' WHERE id=?', [...array_values($f), $id]);
    if ($old['image'] && $old['image'] !== $f['image']) delete_upload($old['image']);
} else {
    $f['slug'] = unique_slug('categories', make_slug($slugIn !== '' ? $slugIn : $name));
    if (!isset($in['sort_order'])) $f['sort_order'] = (int)q('SELECT COALESCE(MAX(sort_order),0)+1 FROM categories')->fetchColumn();
    q('INSERT INTO categories (' . implode(',', array_keys($f)) . ') VALUES (' . implode(',', array_fill(0, count($f), '?')) . ')', array_values($f));
    $id = (int)db()->lastInsertId();
}
ok('دسته ذخیره شد.', ['id' => $id]);
