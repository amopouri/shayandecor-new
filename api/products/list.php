<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
$admin = !empty($_GET['admin']);
if ($admin) require_admin(false);
$page = n($_GET['page'] ?? 1, 1, 100000);
$per  = n($_GET['per_page'] ?? 20, 1, 100);
$where = []; $p = [];
if (!$admin) { $where[] = 'p.is_active=1'; $where[] = '(c.id IS NULL OR c.is_active=1)'; }
$term = s($_GET['q'] ?? '', 80);
if ($term !== '') {
    $like = '%' . addcslashes($term, '%_\\') . '%';
    $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)';
    array_push($p, $like, $like, $like);
}
if (!empty($_GET['category'])) { $where[] = 'p.category_id=?'; $p[] = n($_GET['category']); }
if (!empty($_GET['category_slug'])) { $where[] = 'c.slug=?'; $p[] = s($_GET['category_slug'], 140); }
if (!empty($_GET['category_ids'])) {
    $ids = array_values(array_filter(array_map(fn($v) => n($v, 1, 4294967295), explode(',', (string)$_GET['category_ids']))));
    if ($ids) { $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'; array_push($p, ...$ids); }
}
if (isset($_GET['min_price']) && $_GET['min_price'] !== '') { $where[] = 'p.price>=?'; $p[] = n($_GET['min_price']); }
if (isset($_GET['max_price']) && $_GET['max_price'] !== '') { $where[] = 'p.price<=?'; $p[] = n($_GET['max_price']); }
$colors = $_GET['colors'] ?? [];
if (!is_array($colors)) $colors = $colors === '' ? [] : explode(',', (string)$colors);
$colors = array_values(array_filter(array_map(fn($v) => s($v, 120), $colors)));
if ($colors) {
    $or = [];
    foreach ($colors as $color) { $or[] = 'p.color LIKE ?'; $p[] = '%"' . addcslashes($color, "%_\\") . '"%'; }
    $where[] = '(' . implode(' OR ', $or) . ')';
}
$shanehs = $_GET['shaneh'] ?? [];
if (!is_array($shanehs)) $shanehs = $shanehs === '' ? [] : explode(',', (string)$shanehs);
$shanehs = array_values(array_filter(array_map(fn($v) => s($v, 40), $shanehs)));
if ($shanehs) {
    $or = [];
    foreach ($shanehs as $sh) { $or[] = 'p.shaneh=?'; $p[] = $sh; }
    $where[] = '(' . implode(' OR ', $or) . ')';
}
switch (s($_GET['status'] ?? '', 20)) {
    case 'active':   $where[] = 'p.is_active=1'; break;
    case 'inactive': $where[] = 'p.is_active=0'; break;
    case 'out':      $where[] = 'p.stock<=0'; break;
    case 'sale':     $where[] = '(p.discount>0 OR p.old_price>p.price)'; break;
    case 'new':      $where[] = 'p.is_new=1'; break;
    case 'featured': $where[] = 'p.is_featured=1'; break;
}
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orders = ['newest' => 'p.created_at DESC, p.id DESC', 'price_asc' => 'p.price ASC, p.id', 'price_desc' => 'p.price DESC, p.id', 'name' => 'p.name ASC', 'popular' => 'p.reviews_count DESC, p.rating DESC'];
$O = $orders[$_GET['sort'] ?? 'newest'] ?? $orders['newest'];
$total = (int)q("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id=p.category_id $W", $p)->fetchColumn();
$off = ($page - 1) * $per;
$rows = q("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id $W ORDER BY $O LIMIT $per OFFSET $off", $p)->fetchAll();
$imgs = images_for(array_map(fn($r) => (int)$r['id'], $rows));
$priceMax = (int)(q("SELECT COALESCE(MAX(p.price),0) FROM products p LEFT JOIN categories c ON c.id=p.category_id $W", $p)->fetchColumn() ?: 0);
json_out(['success' => true, 'items' => array_map(fn($r) => shape_product($r, $imgs), $rows), 'total' => $total, 'page' => $page, 'per_page' => $per, 'pages' => (int)ceil($total / $per), 'price_max' => $priceMax]);
