<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!user()) { http_response_code(401); echo '{"ok":false}'; exit; }
echo json_encode(['ok' => true, 'unread' => (int) val('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [user()['id']])]);
