<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$pid = n($_POST['product_id'] ?? 0);
if (!q('SELECT 1 FROM products WHERE id=?', [$pid])->fetch()) fail('محصول پیدا نشد.', 404);
$files = files_list('images');
if (!$files) fail('هیچ تصویری ارسال نشد. (حجم کل درخواست ممکن است از حد سرور بیشتر باشد.)', 422);
$have = (int)q('SELECT COUNT(*) FROM product_images WHERE product_id=?', [$pid])->fetchColumn();
$added = []; $errors = [];
foreach (array_slice($files, 0, 10) as $f) {
    if ($have + count($added) >= 20) { $errors[] = 'حداکثر ۲۰ تصویر برای هر محصول مجاز است.'; break; }
    try {
        $path = save_image($f, 'products');
        $primary = ($have + count($added)) === 0 ? 1 : 0;
        $max = (int)q('SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id=?', [$pid])->fetchColumn();
        q('INSERT INTO product_images (product_id,image_path,is_primary,sort_order) VALUES (?,?,?,?)', [$pid, $path, $primary, $max + 1]);
        $added[] = ['id' => (int)db()->lastInsertId(), 'path' => $path, 'is_primary' => $primary];
    } catch (RuntimeException $e) { $errors[] = s($f['name'], 60) . ': ' . $e->getMessage(); }
}
if (!$added) fail($errors[0] ?? 'آپلود ناموفق بود.', 422, ['errors' => $errors]);
ok(count($added) . ' تصویر آپلود شد.', ['images' => $added, 'errors' => $errors]);
