<?php
/**
 * posts/my_posts.php — список постов текущего пользователя.
 * Владелец/админ видят все посты, автор — только свои.
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('owner','admin','author');

$uid = current_user()['id'];

// Удаление / отправка на проверку
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)($_POST['post_id'] ?? 0);

    // Проверка владения (кроме админа/владельца)
    $chk = db()->prepare("SELECT author_id FROM posts WHERE id = ?");
    $chk->execute([$pid]);
    $ownerId = $chk->fetchColumn();

    if ($ownerId !== false && (has_role('admin','owner') || (int)$ownerId === $uid)) {
        if (($_POST['do'] ?? '') === 'delete') {
            db()->prepare("DELETE FROM posts WHERE id = ?")->execute([$pid]);
        } elseif (($_POST['do'] ?? '') === 'submit') {
            db()->prepare("UPDATE posts SET status='pending', reject_note=NULL WHERE id = ?")->execute([$pid]);
        }
    }
    redirect('posts/my_posts.php');
}

// Выборка
if (has_role('admin','owner')) {
    $stmt = db()->query(
        "SELECT p.*, u.name AS author_name FROM posts p JOIN users u ON u.id=p.author_id
         ORDER BY p.updated_at DESC"
    );
    $posts = $stmt->fetchAll();
    $heading = t('mp.all');
} else {
    $stmt = db()->prepare(
        "SELECT p.*, u.name AS author_name FROM posts p JOIN users u ON u.id=p.author_id
         WHERE p.author_id = ? ORDER BY p.updated_at DESC"
    );
    $stmt->execute([$uid]);
    $posts = $stmt->fetchAll();
    $heading = t('mp.mine');
}

$pageTitle = $heading . ' — 11x11';
require __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-8">
  <h1 class="text-3xl font-extrabold"><?= e($heading) ?></h1>
  <a href="<?= base_url('posts/create.php') ?>"
     class="rounded-xl bg-neon text-pitch-900 font-semibold px-5 py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('dash.new_post')) ?></a>
</div>

<?php if (!$posts): ?>
  <div class="glass rounded-2xl p-12 text-center text-slate-400">
    <div class="text-5xl mb-3 opacity-40">📝</div>
    <?= e(t('mp.empty')) ?> <a href="<?= base_url('posts/create.php') ?>" class="text-neon hover:underline"><?= e(t('mp.create_first')) ?></a>.
  </div>
<?php else: ?>
  <div class="glass rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-left text-slate-400 border-b border-white/10">
          <tr>
            <th class="px-5 py-3 font-medium"><?= e(t('mp.th_title')) ?></th>
            <?php if (has_role('admin','owner')): ?><th class="px-5 py-3 font-medium"><?= e(t('mp.th_author')) ?></th><?php endif; ?>
            <th class="px-5 py-3 font-medium"><?= e(t('mp.th_cat')) ?></th>
            <th class="px-5 py-3 font-medium"><?= e(t('mp.th_status')) ?></th>
            <th class="px-5 py-3 font-medium"><?= e(t('mp.th_upd')) ?></th>
            <th class="px-5 py-3 font-medium text-right"><?= e(t('mp.th_act')) ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-white/5">
          <?php foreach ($posts as $p): ?>
            <tr class="hover:bg-white/5 transition">
              <td class="px-5 py-3">
                <a href="<?= base_url('post.php?id=' . $p['id']) ?>" class="font-semibold hover:text-neon"><?= e(post_field($p,'title')) ?></a>
                <?php if ($p['status']==='rejected' && $p['reject_note']): ?>
                  <div class="text-xs text-rose-300 mt-1">✋ <?= e($p['reject_note']) ?></div>
                <?php endif; ?>
              </td>
              <?php if (has_role('admin','owner')): ?><td class="px-5 py-3 text-slate-300"><?= e($p['author_name']) ?></td><?php endif; ?>
              <td class="px-5 py-3 text-slate-300"><?= e(category_badge_text($p['category'])) ?></td>
              <td class="px-5 py-3"><?= status_badge($p['status']) ?></td>
              <td class="px-5 py-3 text-slate-400"><?= date('d.m.Y H:i', strtotime($p['updated_at'])) ?></td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">
                  <a href="<?= base_url('posts/edit.php?id=' . $p['id']) ?>"
                     class="rounded-lg bg-white/5 hover:bg-white/10 px-3 py-1.5 transition"><?= e(t('mp.edit')) ?></a>

                  <?php if (in_array($p['status'], ['draft','rejected'], true)): ?>
                    <form method="post" class="inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="do" value="submit">
                      <button class="rounded-lg bg-amber-400/90 text-pitch-900 hover:bg-amber-300 px-3 py-1.5 font-medium transition"><?= e(t('mp.submit')) ?></button>
                    </form>
                  <?php endif; ?>

                  <form method="post" class="inline" onsubmit="return confirm('<?= e(t('mp.confirm_del')) ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="do" value="delete">
                    <button class="rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 px-3 py-1.5 transition"><?= e(t('mp.delete')) ?></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
