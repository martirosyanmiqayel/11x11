<?php
/**
 * config/db.php
 * ------------------------------------------------------------
 * Единая точка подключения к MySQL через PDO (защита от инъекций)
 * + авто-инициализация схемы и учётной записи ВЛАДЕЛЬЦА.
 *
 * При первом запуске:
 *   1. Создаёт БД и таблицы из sql/schema.sql (если их нет).
 *   2. Находит/создаёт пользователя 11x11@11x11.com с ролью `owner`.
 *   3. Генерирует СЛОЖНЫЙ пароль и один раз сохраняет его в
 *      config/OWNER_PASSWORD.txt (файл виден только владельцу сервера).
 * ------------------------------------------------------------
 */

// ---- Параметры подключения (стандарт XAMPP) ----------------
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'db11x11';
const DB_USER = 'root';
const DB_PASS = '';          // XAMPP по умолчанию без пароля

const OWNER_EMAIL = '11x11@11x11.com';

/**
 * Возвращает singleton-экземпляр PDO.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // исключения вместо тихих ошибок
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // ассоц. массивы
        PDO::ATTR_EMULATE_PREPARES   => false,                    // настоящие prepared statements
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die('❌ Не удалось подключиться к MySQL. Запущен ли XAMPP? ' . $e->getMessage());
    }

    bootstrap($pdo);            // разово готовим схему и владельца
    $pdo->exec('USE `' . DB_NAME . '`');
    return $pdo;
}

/**
 * Разовая инициализация: схема + владелец.
 * Идемпотентна — повторные вызовы безопасны.
 */
function bootstrap(PDO $pdo): void
{
    // --- 1. Схема из файла (выполняем при отсутствии БД) -----
    $exists = $pdo->query(
        "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA
         WHERE SCHEMA_NAME = " . $pdo->quote(DB_NAME)
    )->fetchColumn();

    if (!$exists) {
        $sqlFile = __DIR__ . '/../sql/schema.sql';
        if (is_readable($sqlFile)) {
            $pdo->exec(file_get_contents($sqlFile));
        }
    }

    $pdo->exec('USE `' . DB_NAME . '`');

    // --- 2. Владелец: создаём при отсутствии -----------------
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([OWNER_EMAIL]);

    if (!$stmt->fetchColumn()) {
        // Криптостойкий пароль: 20 символов base64 из random_bytes
        $plainPassword = rtrim(strtr(base64_encode(random_bytes(15)), '+/', 'Az'), '=');
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $ins = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active)
             VALUES (?, ?, ?, "owner", 1)'
        );
        $ins->execute(['Владелец 11x11', OWNER_EMAIL, $hash]);

        // Сохраняем пароль ОДИН раз в защищённый файл рядом с конфигом
        $note = "==== 11x11 :: доступ ВЛАДЕЛЬЦА ====\n"
              . "Email:  " . OWNER_EMAIL . "\n"
              . "Пароль: " . $plainPassword . "\n"
              . "Создан: " . date('Y-m-d H:i:s') . "\n"
              . "После первого входа удалите этот файл.\n";
        @file_put_contents(__DIR__ . '/OWNER_PASSWORD.txt', $note);
    }
}
