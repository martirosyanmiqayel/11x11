<?php
/**
 * post.php — просмотр одной статьи.
 * Публикация видна всем; черновики/pending — только автору и админам.
 */
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare(
    "SELECT p.*, u.name AS author_name FROM posts p
     JOIN users u ON u.id = p.author_id WHERE p.id = ? LIMIT 1"
);
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = t('post.notfound_title');
    require __DIR__ . '/includes/header.php';
    echo '<div class="glass rounded-2xl p-12 text-center text-slate-400">' . e(t('post.notfound')) . '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Доступ к неопубликованным — автор или админ/владелец
$canSeeDraft = has_role('admin','owner') || (current_user()['id'] ?? 0) === (int)$post['author_id'];
if ($post['status'] !== 'published' && !$canSeeDraft) {
    redirect('index.php');
}

// Счётчик просмотров только для опубликованных
if ($post['status'] === 'published') {
    db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$id]);
}

$pageTitle = post_field($post, 'title');
require __DIR__ . '/includes/header.php';
?>

<article class="mx-auto max-w-3xl">
  <a href="<?= base_url('index.php') ?>" class="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-neon transition mb-6"><?= e(t('post.back')) ?></a>

  <?php if ($post['status'] !== 'published'): ?>
    <div class="mb-4"><?= status_badge($post['status']) ?>
      <span class="text-xs text-slate-400 ml-2"><?= e(t('post.preview')) ?></span></div>
  <?php endif; ?>

  <div class="flex items-center gap-3 mb-4">
    <span class="rounded-full bg-neon/15 text-neon px-3 py-1 text-xs font-semibold"><?= e(category_badge_text($post['category'])) ?></span>
    <span class="text-sm text-slate-400"><?= date('d.m.Y H:i', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
  </div>

  <h1 class="text-3xl sm:text-4xl font-extrabold leading-tight"><?= e(post_field($post,'title')) ?></h1>

  <div class="mt-4 flex items-center gap-3 text-sm text-slate-400 border-b border-white/10 pb-6">
    <span class="grid h-9 w-9 place-items-center rounded-full bg-neon text-pitch-900 font-bold"><?= e(mb_substr($post['author_name'],0,1)) ?></span>
    <span><?= e(t('post.author')) ?> <b class="text-slate-200"><?= e($post['author_name']) ?></b></span>
    <span>· 👁 <?= (int)$post['views'] ?></span>
  </div>

  <?php if ($post['cover_url']): ?>
    <img src="<?= e($post['cover_url']) ?>" alt="" class="mt-6 w-full rounded-2xl">
  <?php endif; ?>

  <div class="prose prose-invert max-w-none mt-8 text-slate-200 leading-relaxed space-y-4">
    <?php
      // Текст автора на текущем языке, с экранированием и сохранением абзацев
      foreach (preg_split('/\n{2,}/', trim(post_field($post,'body'))) as $para) {
          echo '<p>' . nl2br(e($para)) . '</p>';
      }
    ?>
  </div>

  <?php if ($canSeeDraft): ?>
    <div class="mt-10 pt-6 border-t border-white/10">
      <a href="<?= base_url('posts/edit.php?id=' . $post['id']) ?>"
         class="inline-flex items-center gap-2 rounded-lg bg-white/5 hover:bg-white/10 px-4 py-2 text-sm transition"><?= e(t('post.edit')) ?></a>
    </div>
  <?php endif; ?>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
