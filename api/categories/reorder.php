<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$i = 1;
foreach ((array)(input()['order'] ?? []) as $id) q('UPDATE categories SET sort_order=? WHERE id=?', [$i++, n($id)]);
ok('ترتیب دسته‌ها ذخیره شد.');
