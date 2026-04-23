<?php
require_once __DIR__ . '/includes/config.php';
session_destroy();
header('Location: ' . APP_URL . '/index.php');
exit;
