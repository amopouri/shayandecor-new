<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$id = n(input()['id'] ?? 0);
$r = q('SELECT * FROM products WHERE id=?', [$id])->fetch();
if (!$r) fail('محصول پیدا نشد.', 404);
unset($r['id'], $r['created_at'], $r['updated_at']);
$r['name'] = mb_substr($r['name'], 0, 190) . ' (کپی)';
$r['slug'] = unique_slug('products', make_slug($r['name']));
$r['sku'] = null; $r['is_active'] = 0;
q('INSERT INTO products (' . implode(',', array_keys($r)) . ') VALUES (' . implode(',', array_fill(0, count($r), '?')) . ')', array_values($r));
$new = (int)db()->lastInsertId();
foreach (q('SELECT image_path,is_primary,sort_order FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order, id', [$id])->fetchAll() as $im) {
    $path = $im['image_path'];
    if (is_local_upload($path) && is_file(ROOT . '/' . $path)) {
        $np = 'uploads/products/product_' . bin2hex(random_bytes(8)) . '.' . pathinfo($path, PATHINFO_EXTENSION);
        if (@copy(ROOT . '/' . $path, ROOT . '/' . $np)) $path = $np; else continue;
    }
    q('INSERT INTO product_images (product_id,image_path,is_primary,sort_order) VALUES (?,?,?,?)', [$new, $path, $im['is_primary'], $im['sort_order']]);
}
ok('کپی محصول ساخته شد (غیرفعال).', ['id' => $new]);
