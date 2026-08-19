<?php
/**
 * admin/moderation.php — очередь модерации.
 * Доступ: администратор и владелец (checkAccess).
 * Действия: одобрить (→published), отклонить (→rejected c причиной).
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('admin','owner');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)($_POST['post_id'] ?? 0);
    $do  = $_POST['do'] ?? '';

    if ($do === 'approve') {
        // Одобрение → мгновенная публикация
        db()->prepare(
            "UPDATE posts SET status='published', published_at=NOW(), reject_note=NULL WHERE id=?"
        )->execute([$pid]);
    } elseif ($do === 'reject') {
        $note = trim($_POST['note'] ?? '') ?: t('mod.default_reason');
        db()->prepare(
            "UPDATE posts SET status='rejected', reject_note=? WHERE id=?"
        )->execute([$note, $pid]);
    }
    redirect('admin/moderation.php');
}

// Очередь на проверке
$pending = db()->query(
    "SELECT p.*, u.name AS author_name, u.email AS author_email
     FROM posts p JOIN users u ON u.id=p.author_id
     WHERE p.status='pending' ORDER BY p.updated_at ASC"
)->fetchAll();

$pageTitle = t('nav.moderation') . ' — 11x11';
require __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center gap-3 mb-8">
  <h1 class="text-3xl font-extrabold inline-flex items-center gap-2"><span class="text-neon"><?= icon('shield', 'w-7 h-7') ?></span><?= e(t('mod.title')) ?></h1>
  <span class="rounded-full bg-amber-500/20 text-amber-300 px-3 py-1 text-sm ring-1 ring-amber-400/30"><?= e(t('mod.queue', ['n' => count($pending)])) ?></span>
</div>

<?php if (!$pending): ?>
  <div class="glass rounded-2xl p-12 text-center text-slate-400">
    <div class="mb-3 flex justify-center text-neon/40"><?= icon('check', 'w-12 h-12') ?></div>
    <?= e(t('mod.empty')) ?>
  </div>
<?php else: ?>
  <div class="space-y-5">
    <?php foreach ($pending as $p): ?>
      <div class="glass rounded-2xl p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
          <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="rounded-full bg-neon/15 text-neon px-2 py-0.5 text-xs font-semibold"><?= e(category_badge_text($p['category'])) ?></span>
              <span class="text-xs text-slate-400"><?= date('d.m.Y H:i', strtotime($p['updated_at'])) ?></span>
            </div>
            <h2 class="text-xl font-bold"><?= e(post_field($p,'title')) ?></h2>
            <p class="text-sm text-slate-400 mt-1 inline-flex items-center gap-1.5"><?= icon('users', 'w-3.5 h-3.5') ?><?= e($p['author_name']) ?> · <?= e($p['author_email']) ?></p>
            <p class="text-slate-300 mt-3 line-clamp-3"><?= e(post_field($p,'excerpt') ?: mb_substr(strip_tags(post_field($p,'body')),0,200).'…') ?></p>
            <a href="<?= base_url('post.php?id=' . $p['id']) ?>" target="_blank"
               class="inline-flex items-center gap-1 mt-3 text-sm text-neon hover:underline"><?= e(t('mod.preview')) ?><?= icon('external', 'w-3.5 h-3.5') ?></a>
          </div>

          <div class="flex flex-col gap-2 shrink-0 w-full sm:w-auto">
            <!-- Одобрить -->
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="do" value="approve">
              <button class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-neon text-pitch-900 font-semibold px-5 py-2.5 hover:bg-neon-400 shadow-glow transition"><?= icon('check', 'w-4 h-4') ?><?= e(t('mod.approve')) ?></button>
            </form>

            <!-- Отклонить (с причиной) -->
            <form method="post" class="flex gap-2" onsubmit="return confirm('<?= e(t('mod.confirm_reject')) ?>')">
              <?= csrf_field() ?>
              <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="do" value="reject">
              <input type="text" name="note" placeholder="<?= e(t('mod.reason')) ?>"
                     class="flex-1 rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-rose-400">
              <button class="rounded-xl bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 px-4 py-2 font-medium transition"><?= e(t('mod.reject')) ?></button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
