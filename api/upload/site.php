<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('POST');
require_admin();
$dir = in_array($_POST['folder'] ?? 'site', ['site', 'categories'], true) ? $_POST['folder'] : 'site';
if (empty($_FILES['image'])) fail('فایلی ارسال نشد.', 422);
try { $path = save_image($_FILES['image'], $dir); }
catch (RuntimeException $e) { fail($e->getMessage(), 422); }
ok('تصویر آپلود شد.', ['path' => $path]);
