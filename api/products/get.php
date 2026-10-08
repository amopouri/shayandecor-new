<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
$admin = current_user() !== null;
$r = q('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?' . ($admin ? '' : ' AND p.is_active=1'), [n($_GET['id'] ?? 0)])->fetch();
if (!$r) fail('محصول پیدا نشد.', 404);
json_out(['success' => true, 'item' => shape_product($r, images_for([(int)$r['id']]))]);
