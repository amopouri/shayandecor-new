<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$id = n(input()['id'] ?? 0);
if (!q('SELECT 1 FROM products WHERE id=?', [$id])->fetch()) fail('محصول پیدا نشد.', 404);
$paths = array_column(q('SELECT image_path FROM product_images WHERE product_id=?', [$id])->fetchAll(), 'image_path');
q('DELETE FROM products WHERE id=?', [$id]);   // ON DELETE CASCADE رکوردهای product_images را حذف می‌کند
foreach ($paths as $pth) delete_upload($pth);
ok('محصول و تصاویر آن حذف شد.');
