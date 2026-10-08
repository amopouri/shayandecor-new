<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
$all = settings_all();
$st = array_intersect_key($all, array_flip(PUBLIC_SETTINGS));
$maint = ($all['maintenance'] ?? '0') === '1' && current_user() === null;
$cats = array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'slug' => $r['slug'], 'image' => $r['image'], 'count' => $r['models_count'] !== null ? (int)$r['models_count'] : (int)$r['pc']],
    q('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_active=1) pc FROM categories c WHERE c.is_active=1 ORDER BY c.sort_order, c.id')->fetchAll());
$attr = ['colors' => [], 'sizes' => [], 'shaneh' => []];
foreach (q('SELECT type,name,value FROM attributes ORDER BY type, sort_order, id')->fetchAll() as $a) {
    if ($a['type'] === 'color') $attr['colors'][] = [$a['name'], $a['value'] ?: '#888888'];
    elseif ($a['type'] === 'size') $attr['sizes'][] = $a['name'];
    else $attr['shaneh'][] = $a['name'];
}
json_out(['success' => true, 'maintenance' => $maint, 'settings' => $st, 'categories' => $cats, 'attributes' => $attr,
    'testimonials' => q('SELECT name,city,initials,text,rating FROM testimonials WHERE is_active=1 ORDER BY sort_order,id')->fetchAll(),
    'faqs' => q('SELECT question,answer FROM faqs WHERE is_active=1 ORDER BY sort_order,id')->fetchAll(),
    'blog' => q('SELECT title,slug,excerpt,image,category,read_time,published_label FROM blog_posts WHERE is_published=1 ORDER BY sort_order,id LIMIT 6')->fetchAll()]);
