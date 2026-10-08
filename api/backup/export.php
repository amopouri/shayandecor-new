<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
require_admin(false);
@set_time_limit(120);
$name = 'shayandecor-backup-' . date('Ymd-His') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Cache-Control: no-store');
echo "-- Shayan Decor backup " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
$pdo = db();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
usort($tables, fn($a, $b) => (int)in_array($a, ['product_images']) - (int)in_array($b, ['product_images']));
foreach ($tables as $t) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $t)) continue;
    $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
    echo "DROP TABLE IF EXISTS `$t`;\n$create;\n\n";
    if ($t === 'login_attempts') continue;
    $st = $pdo->query("SELECT * FROM `$t`");
    $buf = [];
    while ($row = $st->fetch(PDO::FETCH_NUM)) {
        $buf[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
        if (count($buf) >= 200) { echo "INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n"; $buf = []; }
    }
    if ($buf) echo "INSERT INTO `$t` VALUES\n" . implode(",\n", $buf) . ";\n";
    echo "\n";
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";
