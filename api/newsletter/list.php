<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
require_admin(false);
$rows = q('SELECT id,email,created_at FROM subscribers ORDER BY id DESC LIMIT 2000')->fetchAll();
if (!empty($_GET['csv'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="subscribers.csv"');
    echo "\xEF\xBB\xBFemail,date\n";
    foreach ($rows as $r) echo '"' . str_replace('"', '""', ltrim($r['email'], "=+-@\t\r")) . '",' . $r['created_at'] . "\n";
    exit;
}
json_out(['success' => true, 'items' => $rows]);
