<?php
/**
 * posts/create.php — создание поста.
 * Кнопки: «Сохранить черновик» (draft) и «Отправить на проверку» (pending).
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('owner','admin','author');

$error = null;
$categories = category_keys();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title      = trim($_POST['title'] ?? '');
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $body       = trim($_POST['body'] ?? '');
    $title_en   = trim($_POST['title_en'] ?? '');
    $excerpt_en = trim($_POST['excerpt_en'] ?? '');
    $body_en    = trim($_POST['body_en'] ?? '');
    $category   = in_array($_POST['category'] ?? '', category_keys(), true) ? $_POST['category'] : 'news';
    $cover      = trim($_POST['cover_url'] ?? '');
    // Автор жмёт «черновик» или «на проверку»; админ/владелец могут сразу публиковать
    $action     = $_POST['action'] ?? 'draft';

    // Черновик — достаточно русской версии; отправка/публикация — обе версии обязательны
    $needBoth = in_array($action, ['pending','publish'], true);

    if ($title === '' || $body === '') {
        $error = t('form.req');
    } elseif ($needBoth && ($title_en === '' || $body_en === '')) {
        $error = t('form.req_both');
    } else {
        $status = match ($action) {
            'publish' => has_role('admin','owner') ? 'published' : 'pending',
            'pending' => 'pending',
            default   => 'draft',
        };

        $stmt = db()->prepare(
            "INSERT INTO posts (title, excerpt, body, title_en, excerpt_en, body_en,
                                category, cover_url, status, author_id, published_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $title, $excerpt, $body,
            ($title_en ?: null), ($excerpt_en ?: null), ($body_en ?: null),
            $category, ($cover ?: null),
            $status, current_user()['id'],
            $status === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        redirect('posts/my_posts.php');
    }
}

$pageTitle = t('form.new') . ' — 11x11';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_form.php';   // общая разметка формы
require __DIR__ . '/../includes/footer.php';
