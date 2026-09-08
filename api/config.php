<?php
/**
 * config.php — shared bootstrap for every endpoint in /api.
 * DB connection, CORS headers, and small JSON + JWT helpers.
 * Every endpoint starts with: require __DIR__ . '/config.php';
 */

// ---------------------------------------------------------------- settings
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'lane3');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'lane3-dev-secret-change-me');

// ---------------------------------------------------------------- CORS & Chrome Private Network Access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Access-Control-Request-Private-Network');
header('Access-Control-Allow-Private-Network: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------------------------------------------------------------- DB (PDO)
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    json_response(['error' => 'เชื่อมต่อฐานข้อมูลไม่ได้ — ตรวจสอบค่าตั้งค่าใน config.php: ' . $e->getMessage()], 500);
}

// ---------------------------------------------------------------- JSON helpers
function json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---------------------------------------------------------------- minimal HS256 JWT
// No composer dependency on purpose — this is a small, self-contained implementation.
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}
function jwt_encode(array $payload): string {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + 60 * 60 * 24 * 7; // 7 days
    $body = base64url_encode(json_encode($payload));
    $signature = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$signature";
}
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $body, $signature] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $signature)) return null;
    $payload = json_decode(base64url_decode($body), true);
    if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}

// ---------------------------------------------------------------- auth guards
function current_user(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if (!preg_match('/Bearer\s+(\S+)/', $header, $m)) return null;
    return jwt_decode($m[1]);
}
function require_auth(): array {
    $user = current_user();
    if (!$user) json_response(['error' => 'ต้องเข้าสู่ระบบก่อน'], 401);
    return $user;
}
function require_admin(): array {
    $user = require_auth();
    if (($user['role'] ?? '') !== 'admin') json_response(['error' => 'ต้องเป็นแอดมินเท่านั้น'], 403);
    return $user;
}
