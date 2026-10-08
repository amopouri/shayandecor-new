<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
$admin = !empty($_GET['admin']);
if ($admin) require_admin(false);
$rows = q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id' . ($admin ? '' : ' AND p.is_active=1') . ') AS product_count FROM categories c ' . ($admin ? '' : 'WHERE c.is_active=1 ') . 'ORDER BY c.sort_order, c.id')->fetchAll();
json_out(['success' => true, 'items' => array_map(fn($r) => [
    'id' => (int)$r['id'], 'name' => $r['name'], 'slug' => $r['slug'], 'description' => $r['description'], 'image' => $r['image'],
    'models_count' => $r['models_count'] !== null ? (int)$r['models_count'] : null, 'product_count' => (int)$r['product_count'],
    'sort_order' => (int)$r['sort_order'], 'is_active' => (int)$r['is_active'],
], $rows)]);
