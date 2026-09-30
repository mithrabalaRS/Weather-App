<?php
session_start();

$host = 'localhost';
$dbname = 'weather_phase2';
$dbuser = 'root';
$dbpass = '';
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
$appDirectory = realpath(dirname(__DIR__));
define('APP_BASE_PATH', str_replace(DIRECTORY_SEPARATOR, '/', substr($appDirectory, strlen($documentRoot))));

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_BASE_PATH . '/auth/login.php');
        exit;
    }
}

function requireAdmin()
{
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ' . APP_BASE_PATH . '/dashboard.php');
        exit;
    }
}
