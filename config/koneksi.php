<?php
// Koneksi database (Laragon default: user root, tanpa password)
$DB_HOST = 'localhost';
$DB_NAME = 'crm_loka';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan MySQL di Laragon aktif dan database.sql sudah diimpor. (' . htmlspecialchars($e->getMessage()) . ')');
}

// URL dasar proyek, terdeteksi otomatis dari lokasi folder
$root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$proj = str_replace('\\', '/', realpath(__DIR__ . '/..'));
define('BASE_URL', rtrim(str_replace($root, '', $proj), '/'));
