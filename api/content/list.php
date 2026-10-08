<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
require_admin(false);
$d = content_def();
json_out(['success' => true, 'items' => q("SELECT * FROM {$d['table']} ORDER BY {$d['order']}")->fetchAll()]);
