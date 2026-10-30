<?php
require_once __DIR__ . '/includes/config.php';

header('Location: ' . APP_URL_BASE . (isset($_SESSION['usuario_id']) ? '/dashboard/' : '/login/'));
exit;
