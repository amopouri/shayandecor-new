<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = input();
$id = n($in['id'] ?? 0);
$name = s($in['name'] ?? '', 200);
if ($name === '') fail('نام محصول الزامی است.', 422);
$cat = ($in['category_id'] ?? '') === '' ? null : n($in['category_id']);
if ($cat !== null && !q('SELECT 1 FROM categories WHERE id=?', [$cat])->fetch()) fail('دستهٔ انتخابی وجود ندارد.', 422);
$num = fn($k) => (int)preg_replace('/\D/', '', from_persian_digits((string)($in[$k] ?? '')));
$price = $num('price');
$old = ($in['old_price'] ?? '') === '' ? null : $num('old_price');
$disc = min(95, $num('discount'));
if ($old !== null && $old > $price && $price > 0) $disc = (int)round((1 - $price / $old) * 100);
elseif (($old === null || $old <= $price) && $disc > 0 && $price > 0) $old = (int)round($price / (1 - $disc / 100));
else { $disc = 0; if ($old !== null && $old <= $price) $old = null; }
$sku = s($in['sku'] ?? '', 60); $sku = $sku === '' ? null : $sku;
if ($sku !== null && q('SELECT 1 FROM products WHERE sku=? AND id<>?', [$sku, $id])->fetch()) fail('این SKU قبلاً برای محصول دیگری ثبت شده است.', 422);
$list = function ($v) { $a = is_array($v) ? $v : []; $a = array_slice(array_values(array_unique(array_map(fn($x) => s($x, 100), $a))), 0, 30); return json_encode(array_values(array_filter($a, fn($x) => $x !== '')), JSON_UNESCAPED_UNICODE); };
$rating = max(0, min(5, round((float)($in['rating'] ?? 4.5), 1)));
$shaneh = s($in['shaneh'] ?? '', 20);
$f = [
    'category_id' => $cat, 'name' => $name, 'short_description' => s($in['short_description'] ?? '', 500), 'description' => s($in['description'] ?? '', 8000),
    'features' => s($in['features'] ?? '', 3000), 'price' => $price, 'old_price' => $old, 'discount' => $disc, 'stock' => max(0, (int)from_persian_digits((string)($in['stock'] ?? 0))),
    'sku' => $sku, 'shaneh' => $shaneh === '' ? null : $shaneh, 'size' => $list($in['sizes'] ?? []), 'color' => $list($in['colors'] ?? []),
    'rating' => $rating, 'reviews_count' => n($in['reviews_count'] ?? 0), 'is_new' => b($in['is_new'] ?? 0), 'is_featured' => b($in['is_featured'] ?? 0), 'is_active' => b($in['is_active'] ?? 1),
];
if ($id) {
    if (!q('SELECT 1 FROM products WHERE id=?', [$id])->fetch()) fail('محصول پیدا نشد.', 404);
    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($f)));
    q("UPDATE products SET $set WHERE id=?", [...array_values($f), $id]);
} else {
    $f['slug'] = unique_slug('products', make_slug($name));
    q('INSERT INTO products (' . implode(',', array_keys($f)) . ') VALUES (' . implode(',', array_fill(0, count($f), '?')) . ')', array_values($f));
    $id = (int)db()->lastInsertId();
}
$r = q('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?', [$id])->fetch();
ok('محصول ذخیره شد.', ['item' => shape_product($r, images_for([$id]))]);
