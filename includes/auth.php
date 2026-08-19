<?php
/**
 * includes/auth.php
 * ------------------------------------------------------------
 * Сессии, авторизация, middleware проверки прав, общие хелперы.
 * Подключается ПЕРВЫМ на каждой странице.
 * ------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/categories.php';
init_lang();                       // определяем язык (RU/EN) до любого вывода

/* =========================================================
 *  БАЗОВЫЕ ХЕЛПЕРЫ
 * ========================================================= */

/** Экранирование вывода (XSS-защита). */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Базовый URL проекта (работает и в подпапке htdocs/11x11). */
function base_url(string $path = ''): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // если файл лежит в /posts или /admin — поднимаемся к корню
    $dir = preg_replace('#/(posts|admin)$#', '', $dir);
    return rtrim($dir, '/') . '/' . ltrim($path, '/');
}

/** Редирект с завершением скрипта. */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

/* =========================================================
 *  СОСТОЯНИЕ ПОЛЬЗОВАТЕЛЯ
 * ========================================================= */

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

/** Текущий пользователь или null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_role(): string
{
    return $_SESSION['user']['role'] ?? 'guest';
}

/** Проверка: имеет ли текущий пользователь одну из ролей. */
function has_role(string ...$roles): bool
{
    return in_array(current_role(), $roles, true);
}

/* =========================================================
 *  MIDDLEWARE checkAccess()
 *  Прерывает выполнение, если у пользователя нет нужной роли.
 * ========================================================= */
function checkAccess(string ...$allowedRoles): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
    if ($allowedRoles && !in_array(current_role(), $allowedRoles, true)) {
        http_response_code(403);
        die('<h1 style="font-family:sans-serif;color:#a3e635;background:#052e16;padding:2rem">
                403 — Недостаточно прав для доступа к этому разделу.</h1>');
    }
}

/* =========================================================
 *  ВХОД / РЕГИСТРАЦИЯ / ВЫХОД
 * ========================================================= */

/** Попытка входа. Возвращает true при успехе. */
function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);           // защита от session fixation
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        return true;
    }
    return false;
}

/** Регистрация нового автора. Возвращает [ok, errorMessage]. */
function register_author(string $name, string $email, string $password): array
{
    $name  = trim($name);
    $email = trim($email);

    if (mb_strlen($name) < 2)             return [false, t('err.name_short')];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, t('err.email_bad')];
    if (mb_strlen($password) < 6)         return [false, t('err.pass_short')];

    $check = db()->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetchColumn()) return [false, t('err.email_taken')];

    $ins = db()->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "author")'
    );
    $ins->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    return [true, null];
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/* =========================================================
 *  CSRF-ЗАЩИТА ФОРМ
 * ========================================================= */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        die('Сессия истекла. Обновите страницу и попробуйте снова.');
    }
}

/* =========================================================
 *  ХЕЛПЕРЫ ДЛЯ UI
 * ========================================================= */

/** Человекочитаемый бейдж статуса поста. */
function status_badge(string $status): string
{
    $map = [
        'draft'     => [t('st.draft'),     'bg-slate-500/20 text-slate-300 ring-slate-400/30'],
        'pending'   => [t('st.pending'),   'bg-amber-500/20 text-amber-300 ring-amber-400/30'],
        'published' => [t('st.published'), 'bg-lime-500/20 text-lime-300 ring-lime-400/30'],
        'rejected'  => [t('st.rejected'),  'bg-rose-500/20 text-rose-300 ring-rose-400/30'],
    ];
    [$label, $cls] = $map[$status] ?? ['—', 'bg-slate-500/20 text-slate-300'];
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' . $cls . '">' . $label . '</span>';
}

function transfer_badge(string $status): string
{
    $map = [
        'rumour'      => [t('tr.rumour'),      'bg-slate-500/20 text-slate-300 ring-slate-400/30'],
        'negotiation' => [t('tr.negotiation'), 'bg-amber-500/20 text-amber-300 ring-amber-400/30'],
        'done'        => [t('tr.done'),        'bg-lime-500/20 text-lime-300 ring-lime-400/30'],
        'failed'      => [t('tr.failed'),      'bg-rose-500/20 text-rose-300 ring-rose-400/30'],
    ];
    [$label, $cls] = $map[$status] ?? ['—', 'bg-slate-500/20 text-slate-300'];
    return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' . $cls . '">' . $label . '</span>';
}

/** Русскоязычное название роли. */
function role_label(string $role): string
{
    return ['owner' => t('role.owner'), 'admin' => t('role.admin'), 'author' => t('role.author')][$role] ?? $role;
}
