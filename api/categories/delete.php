<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$id = n(input()['id'] ?? 0);
$c = q('SELECT image FROM categories WHERE id=?', [$id])->fetch();
if (!$c) fail('دسته پیدا نشد.', 404);
$cnt = (int)q('SELECT COUNT(*) FROM products WHERE category_id=?', [$id])->fetchColumn();
if ($cnt > 0) fail("این دسته $cnt محصول دارد. ابتدا محصولات را به دستهٔ دیگری منتقل یا حذف کنید (یا دسته را غیرفعال کنید).", 409);
q('DELETE FROM categories WHERE id=?', [$id]);
delete_upload($c['image']);
ok('دسته حذف شد.');
