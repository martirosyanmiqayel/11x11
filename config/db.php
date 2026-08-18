<?php
/**
 * config/db.php
 * ------------------------------------------------------------
 * Подключение к MySQL через PDO + авто-инициализация.
 *
 * Работает в двух режимах:
 *   • ЛОКАЛЬНО (XAMPP)  — если нет переменных окружения БД,
 *     используются root/без пароля, база создаётся автоматически.
 *   • ОБЛАКО (Railway)  — читает переменные окружения
 *     (MYSQL_URL / DATABASE_URL или MYSQLHOST/MYSQLUSER/...),
 *     подключается к готовой базе и создаёт таблицы.
 *
 * При первом запуске создаёт таблицы, владельца (11x11@11x11.com)
 * и, если задан файл sql/demo_posts.json, наполняет ленту новостями.
 * ------------------------------------------------------------
 */

const OWNER_EMAIL = '11x11@11x11.com';

/** Определяет параметры подключения: окружение (облако) или XAMPP. */
function db_config(): array
{
    // 1) Строка подключения одной переменной (Railway даёт MYSQL_URL)
    foreach (['MYSQL_URL', 'DATABASE_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL'] as $key) {
        $url = getenv($key);
        if ($url) {
            $p = parse_url($url);
            if ($p && !empty($p['host'])) {
                return [
                    'host'    => $p['host'],
                    'port'    => (string)($p['port'] ?? 3306),
                    'user'    => urldecode($p['user'] ?? 'root'),
                    'pass'    => urldecode($p['pass'] ?? ''),
                    'name'    => ltrim($p['path'] ?? '', '/') ?: 'railway',
                    'managed' => true,
                ];
            }
        }
    }
    // 2) Отдельные переменные (Railway MYSQL* или свои DB_*)
    $host = getenv('MYSQLHOST') ?: getenv('DB_HOST');
    if ($host) {
        return [
            'host'    => $host,
            'port'    => (string)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306),
            'user'    => getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root',
            'pass'    => getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '',
            'name'    => getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway',
            'managed' => true,
        ];
    }
    // 3) Локальный XAMPP по умолчанию
    return ['host' => '127.0.0.1', 'port' => '3306', 'user' => 'root', 'pass' => '', 'name' => 'db11x11', 'managed' => false];
}

/** Singleton PDO. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $c = db_config();
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if ($c['managed']) {
            // Облако: база уже создана — подключаемся к ней напрямую
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
        } else {
            // Локально: подключаемся без БД, создаём её при необходимости
            $dsn = "mysql:host={$c['host']};port={$c['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $c['user'], $c['pass'], $options);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$c['name']}`
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$c['name']}`");
        }
    } catch (PDOException $e) {
        http_response_code(500);
        die('❌ Нет подключения к базе данных. ' . ($c['managed'] ? '' : 'Запущен ли MySQL в XAMPP? ') . $e->getMessage());
    }

    bootstrap($pdo);
    return $pdo;
}

/** Разовая инициализация: таблицы → владелец → демо-новости. Идемпотентна. */
function bootstrap(PDO $pdo): void
{
    // Есть ли таблица users? Если нет — создаём всю схему.
    $hasUsers = false;
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        $hasUsers = true;
    } catch (PDOException $e) { /* таблицы ещё нет */ }

    if (!$hasUsers) {
        run_sql_file($pdo, __DIR__ . '/../sql/schema.sql');
    }

    ensure_owner($pdo);
    ensure_demo($pdo);
}

/** Выполняет .sql-файл, пропуская CREATE DATABASE / USE (БД уже выбрана). */
function run_sql_file(PDO $pdo, string $file): void
{
    if (!is_readable($file)) return;
    $sql = file_get_contents($file);

    // Разбиваем по ';' в конце строки (в схеме нет ';' внутри значений)
    foreach (preg_split('/;\s*[\r\n]/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || str_starts_with($stmt, '--')) continue;
        if (preg_match('/^\s*(CREATE\s+DATABASE|USE)\b/i', $stmt)) continue;
        try { $pdo->exec($stmt); } catch (PDOException $e) { /* пропускаем безобидные */ }
    }
}

/** Создаёт владельца, если его нет. Пароль — из env OWNER_PASSWORD или случайный. */
function ensure_owner(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([OWNER_EMAIL]);
    if ($stmt->fetchColumn()) return;

    $plain = getenv('OWNER_PASSWORD') ?: rtrim(strtr(base64_encode(random_bytes(15)), '+/', 'Az'), '=');
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active)
                   VALUES (?, ?, ?, "owner", 1)')
        ->execute(['Владелец 11x11', OWNER_EMAIL, password_hash($plain, PASSWORD_DEFAULT)]);

    // Локально — в файл; в облаке — в лог (файловая система эфемерна)
    $note = "==== 11x11 :: доступ ВЛАДЕЛЬЦА ====\nEmail: " . OWNER_EMAIL . "\nПароль: {$plain}\n";
    if (!@file_put_contents(__DIR__ . '/OWNER_PASSWORD.txt', $note)) {
        error_log("[11x11] OWNER login: " . OWNER_EMAIL . " / password: {$plain}");
    }
}

/** Наполняет ленту демо-новостями из sql/demo_posts.json, если постов нет. */
function ensure_demo(PDO $pdo): void
{
    if ((int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn() > 0) return;
    $file = __DIR__ . '/../sql/demo_posts.json';
    if (!is_readable($file)) return;
    $rows = json_decode(file_get_contents($file), true);
    if (!is_array($rows)) return;

    $ownerId = (int)$pdo->query("SELECT id FROM users WHERE role='owner' LIMIT 1")->fetchColumn();
    if (!$ownerId) return;

    $ins = $pdo->prepare(
        "INSERT INTO posts (title,title_en,excerpt,excerpt_en,body,body_en,category,cover_url,status,author_id,views,published_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    foreach ($rows as $r) {
        $ins->execute([
            $r['title'] ?? '', $r['title_en'] ?? null, $r['excerpt'] ?? null, $r['excerpt_en'] ?? null,
            $r['body'] ?? '', $r['body_en'] ?? null, $r['category'] ?? 'news', $r['cover_url'] ?? null,
            $r['status'] ?? 'published', $ownerId, (int)($r['views'] ?? 0),
            $r['published_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }
}
