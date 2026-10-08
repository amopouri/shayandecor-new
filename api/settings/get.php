<?php
require __DIR__ . '/../../lib/bootstrap.php';
require_method('GET');
require_admin(false);
$all = settings_all();
json_out(['success' => true, 'settings' => array_intersect_key($all, array_flip(ALL_SETTINGS))]);
