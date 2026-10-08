<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/lib/bootstrap.php';
require_method('POST'); require_admin(true);
$id=n(input()['id']??0); if(!$id) fail('شناسه نامعتبر است.',422);
if(!q('SELECT id FROM attributes WHERE id=?',[$id])->fetch()) fail('ویژگی پیدا نشد.',404);
q('DELETE FROM attributes WHERE id=?',[$id]); ok('ویژگی حذف شد.');
