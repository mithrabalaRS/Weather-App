<?php
require_once __DIR__ . '/../config/db.php';

session_unset();
session_destroy();

header('Location: ' . APP_BASE_PATH . '/auth/login.php');
exit;
