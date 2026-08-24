<?php
declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'commute_share';
const DB_USER = 'root';
const DB_PASS = '';
// Put your browser-restricted Google Maps API key here.
const GOOGLE_MAPS_API_KEY = 'AIzaSyCkzoc7ZbX-wVgMRcoMyCF_WVDbMpjTXsI';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    return $pdo;
}

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function require_login(): int {
    if (empty($_SESSION['user_id'])) json_response(['ok'=>false,'error'=>'Authentication required'], 401);
    return (int)$_SESSION['user_id'];
}

function clean(string $value): string {
    return trim($value);
}

function user_by_id(int $id): ?array {
    $st = db()->prepare('SELECT id,name,email,phone,vehicle,vehicle_no,city,created_at FROM users WHERE id=?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}
