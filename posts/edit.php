<?php
/**
 * posts/edit.php — редактирование поста.
 * Автор может править только свои посты; админ/владелец — любые.
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('owner','admin','author');

$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$uid = current_user()['id'];
$categories = category_keys();

// Загружаем пост
$stmt = db()->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) redirect('posts/my_posts.php');

// Право на редактирование
$isOwnerOrAdmin = has_role('admin','owner');
if (!$isOwnerOrAdmin && (int)$post['author_id'] !== $uid) {
    http_response_code(403);
    die('Нет прав на редактирование этого поста.');
}

$error = null;
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
    $action     = $_POST['action'] ?? 'draft';

    $needBoth = in_array($action, ['pending','publish'], true);

    if ($title === '' || $body === '') {
        $error = t('form.req');
    } elseif ($needBoth && ($title_en === '' || $body_en === '')) {
        $error = t('form.req_both');
    } else {
        // Определяем новый статус
        $status = match ($action) {
            'publish' => has_role('admin','owner') ? 'published' : 'pending',
            'pending' => 'pending',
            default   => 'draft',
        };
        $publishedAt = $post['published_at'];
        if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');

        $upd = db()->prepare(
            "UPDATE posts SET title=?, excerpt=?, body=?, title_en=?, excerpt_en=?, body_en=?,
                    category=?, cover_url=?, status=?, published_at=?, reject_note=NULL WHERE id=?"
        );
        $upd->execute([
            $title, $excerpt, $body,
            ($title_en ?: null), ($excerpt_en ?: null), ($body_en ?: null),
            $category, ($cover ?: null), $status, $publishedAt, $id,
        ]);

        redirect('posts/my_posts.php');
    }
}

// Предзаполняем форму данными поста
$_POST = array_merge($post, $_POST);
$pageTitle = t('form.edit') . ' — 11x11';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/_form.php';
require __DIR__ . '/../includes/footer.php';
