<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$in = input();
$id = n($in['id'] ?? 0);
$field = $in['field'] ?? '';
$val = (int)preg_replace('/[^\d\-]/', '', from_persian_digits((string)($in['value'] ?? '0')));
if (!q('SELECT 1 FROM products WHERE id=?', [$id])->fetch()) fail('محصول پیدا نشد.', 404);
switch ($field) {
    case 'stock': q('UPDATE products SET stock=? WHERE id=?', [max(0, $val), $id]); break;
    case 'is_active': q('UPDATE products SET is_active=? WHERE id=?', [$val ? 1 : 0, $id]); break;
    case 'is_featured': q('UPDATE products SET is_featured=? WHERE id=?', [$val ? 1 : 0, $id]); break;
    case 'price':
        $val = max(0, $val);
        $old = q('SELECT old_price FROM products WHERE id=?', [$id])->fetchColumn();
        $old = $old !== null ? (int)$old : null;
        $disc = ($old && $old > $val && $val > 0) ? (int)round((1 - $val / $old) * 100) : 0;
        if ($old !== null && $old <= $val) $old = null;
        q('UPDATE products SET price=?, old_price=?, discount=? WHERE id=?', [$val, $old, $disc, $id]);
        break;
    default: fail('فیلد نامعتبر است.', 422);
}
ok('ذخیره شد.');
