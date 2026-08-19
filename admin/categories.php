<?php
/**
 * admin/categories.php — управление категориями. Доступ: только владелец.
 * Создание / редактирование / удаление двуязычных категорий с иконками.
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('owner');

$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';

    if ($do === 'create' || $do === 'update') {
        $slug    = strtolower(trim($_POST['slug'] ?? ''));
        $name_ru = trim($_POST['name_ru'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $icon    = trim($_POST['icon'] ?? '');
        $kw      = trim($_POST['keywords'] ?? '');
        $order   = (int)($_POST['sort_order'] ?? 0);

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $notice = ['err', t('cats.slug_bad')];
        } elseif ($name_ru === '' || $name_en === '') {
            $notice = ['err', t('usr.bad_fields')];
        } else {
            if ($do === 'create') {
                $exists = db()->prepare("SELECT id FROM categories WHERE slug=?");
                $exists->execute([$slug]);
                if ($exists->fetchColumn()) {
                    $notice = ['err', t('cats.slug_exists')];
                } else {
                    db()->prepare("INSERT INTO categories (slug,name_ru,name_en,icon,keywords,sort_order) VALUES (?,?,?,?,?,?)")
                        ->execute([$slug,$name_ru,$name_en,$icon,$kw,$order]);
                    $notice = ['ok', t('cats.created')];
                }
            } else { // update по id (slug не меняем, чтобы не осиротить посты)
                $id = (int)($_POST['id'] ?? 0);
                db()->prepare("UPDATE categories SET name_ru=?,name_en=?,icon=?,keywords=?,sort_order=? WHERE id=?")
                    ->execute([$name_ru,$name_en,$icon,$kw,$order,$id]);
                $notice = ['ok', t('cats.updated')];
            }
        }
    } elseif ($do === 'delete') {
        db()->prepare("DELETE FROM categories WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
        $notice = ['ok', t('cats.deleted')];
    }
}

// Категории + количество постов в каждой
$rows = db()->query(
    "SELECT c.*, (SELECT COUNT(*) FROM posts p WHERE p.category = c.slug) AS post_count
     FROM categories c ORDER BY c.sort_order, c.name_en"
)->fetchAll();

$pageTitle = t('nav.categories') . ' — 11x11';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-extrabold mb-2 inline-flex items-center gap-2"><span class="text-neon"><?= icon('tag', 'w-7 h-7') ?></span><?= e(t('cats.title')) ?></h1>
<p class="text-slate-400 mb-8"><?= e(t('cats.sub')) ?></p>

<?php if ($notice): ?>
  <div class="mb-6 rounded-lg px-4 py-3 text-sm <?= $notice[0]==='ok'
        ? 'bg-lime-500/15 ring-1 ring-lime-400/30 text-lime-300'
        : 'bg-rose-500/15 ring-1 ring-rose-400/30 text-rose-300' ?>"><?= e($notice[1]) ?></div>
<?php endif; ?>

<div class="grid gap-8 lg:grid-cols-3">
  <!-- Форма добавления -->
  <div class="lg:col-span-1">
    <div class="glass rounded-2xl p-6 sticky top-24">
      <h2 class="font-bold text-lg mb-4"><?= e(t('cats.new')) ?></h2>
      <form method="post" class="space-y-3">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="create">
        <div>
          <label class="block text-xs text-slate-400 mb-1"><?= e(t('cats.slug')) ?></label>
          <input name="slug" required placeholder="laliga" class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2.5 outline-none focus:border-neon">
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1"><?= e(t('cats.name_ru')) ?></label>
          <input name="name_ru" required class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2.5 outline-none focus:border-neon">
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1"><?= e(t('cats.name_en')) ?></label>
          <input name="name_en" required class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2.5 outline-none focus:border-neon">
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1"><?= e(t('cats.keywords')) ?></label>
          <textarea name="keywords" rows="2" placeholder="messi|barcelona|барселона"
                    class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2.5 text-sm outline-none focus:border-neon"></textarea>
          <p class="text-[11px] text-slate-500 mt-1"><?= e(t('cats.kw_hint')) ?></p>
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1"><?= e(t('cats.order')) ?></label>
          <input name="sort_order" type="number" value="10" class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-3 py-2.5 outline-none focus:border-neon">
        </div>
        <button class="w-full rounded-xl bg-neon text-pitch-900 font-semibold py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('cats.add')) ?></button>
      </form>
    </div>
  </div>

  <!-- Список категорий (инлайн-редактирование) -->
  <div class="lg:col-span-2 space-y-3">
    <?php foreach ($rows as $r): ?>
      <form method="post" class="glass rounded-2xl p-4 flex flex-wrap items-end gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="update">
        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <input type="hidden" name="icon" value="<?= e($r['icon']) ?>">
        <div class="flex-1 min-w-[120px]">
          <label class="block text-[11px] text-slate-400 mb-1"><?= e(t('cats.name_ru')) ?> <span class="text-slate-600">/ <?= e($r['slug']) ?></span></label>
          <input name="name_ru" value="<?= e($r['name_ru']) ?>" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 outline-none focus:border-neon">
        </div>
        <div class="flex-1 min-w-[120px]">
          <label class="block text-[11px] text-slate-400 mb-1"><?= e(t('cats.name_en')) ?></label>
          <input name="name_en" value="<?= e($r['name_en']) ?>" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 outline-none focus:border-neon">
        </div>
        <div class="w-full">
          <label class="block text-[11px] text-slate-400 mb-1"><?= e(t('cats.keywords')) ?></label>
          <input name="keywords" value="<?= e($r['keywords']) ?>" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon">
        </div>
        <div class="w-20">
          <label class="block text-[11px] text-slate-400 mb-1"><?= e(t('cats.order')) ?></label>
          <input name="sort_order" type="number" value="<?= (int)$r['sort_order'] ?>" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-2 py-2 outline-none focus:border-neon">
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-500"><?= (int)$r['post_count'] ?> <?= e(t('cats.posts')) ?></span>
          <button class="rounded-lg bg-neon text-pitch-900 font-semibold px-4 py-2 text-sm hover:bg-neon-400 transition"><?= e(t('cats.save')) ?></button>
        </div>
        <button type="submit" name="do" value="delete" onclick="return confirm('<?= e(t('cats.confirm_del')) ?>')"
                class="rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 px-3 py-2 text-sm transition"><?= e(t('cats.delete')) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
