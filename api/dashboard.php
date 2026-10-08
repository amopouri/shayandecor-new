<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
require_admin(false);
$c = q("SELECT COUNT(*) total, COALESCE(SUM(is_active=1),0) active, COALESCE(SUM(is_active=0),0) inactive, COALESCE(SUM(stock<=0),0) out_of_stock, COALESCE(SUM(discount>0 OR old_price>price),0) on_sale FROM products")->fetch();
$recent = q('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC, p.id DESC LIMIT 6')->fetchAll();
$imgs = images_for(array_map(fn($r) => (int)$r['id'], $recent));
json_out(['success' => true,
    'stats' => array_map('intval', $c) + ['categories' => (int)q('SELECT COUNT(*) FROM categories')->fetchColumn(), 'subscribers' => (int)q('SELECT COUNT(*) FROM subscribers')->fetchColumn()],
    'per_category' => q('SELECT c.name, COUNT(p.id) AS n FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.sort_order, c.id')->fetchAll(),
    'recent' => array_map(fn($r) => shape_product($r, $imgs), $recent)]);
