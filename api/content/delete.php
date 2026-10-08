<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$d = content_def();
$id = n(input()['id'] ?? 0);
$old = q("SELECT * FROM {$d['table']} WHERE id=?", [$id])->fetch();
if (!$old) fail('مورد پیدا نشد.', 404);
q("DELETE FROM {$d['table']} WHERE id=?", [$id]);
delete_upload($old['image'] ?? '');
ok('حذف شد.');
