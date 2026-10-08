<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
q('DELETE FROM subscribers WHERE id=?', [n(input()['id'] ?? 0)]);
ok('حذف شد.');
