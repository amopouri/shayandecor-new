<?php
require __DIR__ . '/../../lib/bootstrap.php';
$u = current_user();
if (!$u) json_out(['success' => true, 'logged_in' => false]);
json_out(['success' => true, 'logged_in' => true, 'user' => $u, 'csrf' => csrf_token(), 'ttl' => SESSION_TTL]);
