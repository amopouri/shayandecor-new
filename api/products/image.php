<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = input();
$act = $in['action'] ?? '';
if ($act === 'add_url') {
    $pid = n($in['product_id'] ?? 0);
    if (!q('SELECT 1 FROM products WHERE id=?', [$pid])->fetch()) fail('محصول پیدا نشد.', 404);
    $raw = is_array($in['urls'] ?? null) ? $in['urls'] : preg_split('/\R+/', (string)($in['urls'] ?? ''));
    $urls = [];
    foreach ($raw as $url) {
        $url = trim((string)$url);
        if ($url === '') continue;
        if (!preg_match("#^https://[^\\s]{4,480}$#i", $url) || preg_match('/["\'<>]/', $url)) fail('فقط URL معتبر HTTPS برای تصویر مجاز است.', 422);
        if (!in_array($url, $urls, true)) $urls[] = $url;
    }
    if (!$urls) fail('حداقل یک URL تصویر وارد کنید.', 422);
    $have = (int)q('SELECT COUNT(*) FROM product_images WHERE product_id=?', [$pid])->fetchColumn();
    if ($have + count($urls) > 20) fail('حداکثر ۲۰ تصویر برای هر محصول مجاز است.', 422);
    $primary = $have === 0 ? 1 : 0;
    $max = (int)q('SELECT COALESCE(MAX(sort_order),-1) FROM product_images WHERE product_id=?', [$pid])->fetchColumn();
    $added=[];
    foreach ($urls as $url) {
        q('INSERT INTO product_images (product_id,image_path,is_primary,sort_order) VALUES (?,?,?,?)',
          [$pid,$url,$primary,++$max]);
        $added[]=['id'=>(int)db()->lastInsertId(),'path'=>$url,'is_primary'=>$primary];
        $primary=0;
    }
    ok(count($added).' تصویر ابری اضافه شد.', ['images'=>$added]);
}
if ($act === 'reorder') {
    $pid = n($in['product_id'] ?? 0); $i = 0;
    foreach ((array)($in['order'] ?? []) as $imgId) q('UPDATE product_images SET sort_order=? WHERE id=? AND product_id=?', [$i++, n($imgId), $pid]);
    ok('ترتیب تصاویر ذخیره شد.');
}
$im = q('SELECT * FROM product_images WHERE id=?', [n($in['image_id'] ?? 0)])->fetch();
if (!$im) fail('تصویر پیدا نشد.', 404);
$pid = (int)$im['product_id'];
if ($act === 'primary') {
    q('UPDATE product_images SET is_primary=0 WHERE product_id=?', [$pid]);
    q('UPDATE product_images SET is_primary=1, sort_order=-1 WHERE id=?', [$im['id']]);
    ok('تصویر اصلی تغییر کرد.');
}
if ($act === 'delete') {
    q('DELETE FROM product_images WHERE id=?', [$im['id']]);
    delete_upload($im['image_path']);
    if ($im['is_primary']) {
        $next = q('SELECT id FROM product_images WHERE product_id=? ORDER BY sort_order,id LIMIT 1', [$pid])->fetchColumn();
        if ($next) q('UPDATE product_images SET is_primary=1 WHERE id=?', [$next]);
    }
    ok('تصویر حذف شد.');
}
fail('عملیات نامعتبر است.', 422);
