<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/lib/bootstrap.php';
require_admin(false);
$type = s($_GET['type'] ?? 'size', 20);
if (!in_array($type, ['size','color','shaneh'], true)) fail('نوع ویژگی نامعتبر است.', 422);
$items = q('SELECT id,type,name,value,sort_order FROM attributes WHERE type=? ORDER BY sort_order,id', [$type])->fetchAll();
ok('فهرست ویژگی‌ها', ['items'=>$items]);
