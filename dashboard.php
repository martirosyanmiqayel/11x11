<?php
/**
 * dashboard.php — стартовая панель после входа.
 * Содержимое зависит от роли (checkAccess требует авторизацию).
 */
require_once __DIR__ . '/includes/auth.php';
checkAccess('owner','admin','author');

$uid  = current_user()['id'];
$role = current_role();

// --- Статистика под роль ------------------------------------
$stats = [];
if (has_role('owner','admin')) {
    $stats[t('dash.users')]     = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats[t('dash.on_mod')]    = db()->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
    $stats[t('dash.published')] = db()->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
    $stats[t('dash.transfers')] = db()->query("SELECT COUNT(*) FROM transfers")->fetchColumn();
} else {
    $s = db()->prepare("SELECT
          SUM(status='draft')     AS drafts,
          SUM(status='pending')   AS pending,
          SUM(status='published') AS published,
          SUM(status='rejected')  AS rejected
        FROM posts WHERE author_id = ?");
    $s->execute([$uid]);
    $r = $s->fetch();
    $stats = [
      t('dash.drafts')    => (int)$r['drafts'],
      t('dash.pending')   => (int)$r['pending'],
      t('dash.published') => (int)$r['published'],
      t('dash.rejected')  => (int)$r['rejected'],
    ];
}

$pageTitle = t('nav.my_posts') . ' — 11x11';
require __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-8">
  <div>
    <h1 class="text-3xl font-extrabold"><?= e(t('dash.hi')) ?><span class="text-neon"><?= e(current_user()['name']) ?></span> 👋</h1>
    <p class="text-slate-400 mt-1"><?= e(t('dash.role_is')) ?> <b class="text-slate-200"><?= e(role_label($role)) ?></b></p>
  </div>
  <a href="<?= base_url('posts/create.php') ?>"
     class="rounded-xl bg-neon text-pitch-900 font-semibold px-5 py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('dash.new_post')) ?></a>
</div>

<!-- Плитки статистики -->
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-10">
  <?php foreach ($stats as $label => $val): ?>
    <div class="glass rounded-2xl p-6">
      <div class="text-4xl font-extrabold text-neon"><?= (int)$val ?></div>
      <div class="mt-1 text-sm text-slate-400"><?= e($label) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Быстрые действия под роль -->
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
  <a href="<?= base_url('posts/my_posts.php') ?>" class="glass glass-hover rounded-2xl p-6 transition">
    <div class="text-3xl mb-3">📝</div>
    <h3 class="font-bold text-lg"><?= e(t('dash.card_myposts')) ?></h3>
    <p class="text-sm text-slate-400 mt-1"><?= e(t('dash.card_myposts_d')) ?></p>
  </a>

  <?php if (has_role('admin','owner')): ?>
    <a href="<?= base_url('admin/moderation.php') ?>" class="glass glass-hover rounded-2xl p-6 transition">
      <div class="text-3xl mb-3">🛡️</div>
      <h3 class="font-bold text-lg text-amber-300"><?= e(t('dash.card_mod')) ?></h3>
      <p class="text-sm text-slate-400 mt-1"><?= e(t('dash.card_mod_d')) ?></p>
    </a>
  <?php endif; ?>

  <?php if (has_role('owner')): ?>
    <a href="<?= base_url('admin/categories.php') ?>" class="glass glass-hover rounded-2xl p-6 transition">
      <div class="text-3xl mb-3">🏷️</div>
      <h3 class="font-bold text-lg"><?= e(t('nav.categories')) ?></h3>
      <p class="text-sm text-slate-400 mt-1"><?= e(t('cats.sub')) ?></p>
    </a>
    <a href="<?= base_url('admin/users.php') ?>" class="glass glass-hover rounded-2xl p-6 transition">
      <div class="text-3xl mb-3">👑</div>
      <h3 class="font-bold text-lg text-neon"><?= e(t('dash.card_access')) ?></h3>
      <p class="text-sm text-slate-400 mt-1"><?= e(t('dash.card_access_d')) ?></p>
    </a>
  <?php endif; ?>

  <a href="<?= base_url('transfers.php') ?>" class="glass glass-hover rounded-2xl p-6 transition">
    <div class="text-3xl mb-3">🔁</div>
    <h3 class="font-bold text-lg"><?= e(t('dash.card_tr')) ?></h3>
    <p class="text-sm text-slate-400 mt-1"><?= e(t('dash.card_tr_d')) ?></p>
  </a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
