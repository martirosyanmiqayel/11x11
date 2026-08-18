<?php
/**
 * index.php — публичная лента опубликованных постов + поиск.
 */
require_once __DIR__ . '/includes/auth.php';

$q        = trim($_GET['q'] ?? '');
$category = trim($_GET['cat'] ?? '');

// --- Выборка опубликованных постов (PDO, параметризовано) ---
$sql    = "SELECT p.*, u.name AS author_name
           FROM posts p JOIN users u ON u.id = p.author_id
           WHERE p.status = 'published'";
$params = [];

if ($q !== '') {
    // Поиск по заголовку / анонсу / тексту — сразу на обоих языках (RU + EN)
    $sql .= " AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.body LIKE ?
                   OR p.title_en LIKE ? OR p.excerpt_en LIKE ? OR p.body_en LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
if ($category !== '') {
    $sql .= " AND p.category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY p.published_at DESC, p.id DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Список категорий для фильтра-чипов
$cats = db()->query("SELECT DISTINCT category FROM posts WHERE status='published' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = '11x11 — ' . t('nav.feed');
require __DIR__ . '/includes/header.php';
?>

<?php
// Топ-новость показываем только на «чистой» ленте (без поиска и фильтра)
$featured = null; $list = $posts;
if ($q === '' && $category === '' && $posts) { $featured = $posts[0]; $list = array_slice($posts, 1); }
// Хелпер атрибута поиска
$searchAttr = fn($p) => e(mb_strtolower($p['title'].' '.$p['title_en'].' '.$p['excerpt'].' '.$p['excerpt_en'].' '.category_label($p['category'])));
?>

<!-- Фильтр категорий + мобильный поиск -->
<div class="mb-6 space-y-4">
  <form method="get" class="md:hidden">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('idx.search')) ?>"
           class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 text-sm outline-none focus:border-neon">
  </form>

  <div class="flex flex-wrap items-center gap-2">
    <a href="<?= base_url('index.php') ?>"
       class="rounded-full px-3 py-1.5 text-sm ring-1 transition <?= $category==='' ? 'bg-neon text-pitch-900 ring-neon' : 'ring-white/15 hover:ring-neon/50' ?>"><?= e(t('idx.all')) ?></a>
    <?php foreach ($cats as $c): ?>
      <a href="<?= base_url('index.php?cat=' . urlencode($c)) ?>"
         class="rounded-full px-3 py-1.5 text-sm ring-1 transition <?= $category===$c ? 'bg-neon text-pitch-900 ring-neon' : 'ring-white/15 hover:ring-neon/50' ?>"><?= e(category_badge_text($c)) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($q !== ''): ?>
    <p class="text-sm text-slate-400"><?= t('idx.results', ['q' => '<b class="text-neon">' . e($q) . '</b>', 'n' => count($posts)]) ?></p>
  <?php endif; ?>
</div>

<?php if (!$posts): ?>
  <div class="glass rounded-2xl p-12 text-center text-slate-400">
    <div class="text-5xl mb-3 opacity-40">🗞️</div>
    <?= e(t('idx.empty')) ?><?= $q ? e(t('idx.empty_q')) : '' ?>.
  </div>
<?php else: ?>

  <!-- ====== ТОП-НОВОСТЬ ====== -->
  <?php if ($featured): $f = $featured; ?>
    <a href="<?= base_url('post.php?id=' . $f['id']) ?>"
       class="feed-card group block glass glass-hover rounded-3xl overflow-hidden mb-8 transition"
       data-search="<?= $searchAttr($f) ?>">
      <div class="grid md:grid-cols-2">
        <div class="relative h-56 md:h-full min-h-[16rem] bg-gradient-to-br from-pitch-700 to-pitch-900 grid place-items-center text-6xl overflow-hidden">
          <?php if ($f['cover_url']): ?>
            <img src="<?= e($f['cover_url']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition duration-500">
          <?php else: ?>⚽<?php endif; ?>
        </div>
        <div class="p-6 sm:p-8 flex flex-col justify-center">
          <div class="flex items-center gap-2 mb-3 text-xs">
            <span class="rounded-full bg-neon text-pitch-900 px-2.5 py-0.5 font-bold uppercase tracking-wide"><?= e(category_badge_text($f['category'])) ?></span>
            <span class="text-slate-400"><?= date('d.m.Y · H:i', strtotime($f['published_at'] ?? $f['created_at'])) ?></span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold leading-tight group-hover:text-neon transition"><?= e(post_field($f,'title')) ?></h2>
          <p class="mt-3 text-slate-300 line-clamp-3"><?= e(post_field($f,'excerpt') ?: mb_substr(strip_tags(post_field($f,'body')),0,180).'…') ?></p>
          <span class="mt-5 inline-flex items-center gap-1 text-neon font-semibold text-sm"><?= e(t('idx.read')) ?> →</span>
        </div>
      </div>
    </a>
  <?php endif; ?>

  <!-- ====== ЛЕНТА НОВОСТЕЙ (строки) ====== -->
  <div class="flex items-center gap-3 mb-4">
    <h3 class="text-lg font-extrabold uppercase tracking-wide text-slate-200"><?= e(t('idx.latest')) ?></h3>
    <div class="h-px flex-1 bg-white/10"></div>
  </div>

  <div id="feed" class="grid gap-4 lg:grid-cols-2">
    <?php foreach ($list as $p): ?>
      <a href="<?= base_url('post.php?id=' . $p['id']) ?>"
         class="feed-card group flex gap-4 glass glass-hover rounded-2xl p-3 transition"
         data-search="<?= $searchAttr($p) ?>">
        <div class="relative h-24 w-32 sm:w-40 shrink-0 rounded-xl overflow-hidden bg-gradient-to-br from-pitch-700 to-pitch-900 grid place-items-center text-3xl">
          <?php if ($p['cover_url']): ?>
            <img src="<?= e($p['cover_url']) ?>" alt="" class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition duration-500">
          <?php else: ?>⚽<?php endif; ?>
        </div>
        <div class="min-w-0 flex flex-col py-0.5">
          <div class="flex items-center gap-2 mb-1 text-[11px]">
            <span class="rounded-full bg-neon/15 text-neon px-2 py-0.5 font-semibold uppercase tracking-wide"><?= e(category_badge_text($p['category'])) ?></span>
            <span class="text-slate-400"><?= date('d.m.Y · H:i', strtotime($p['published_at'] ?? $p['created_at'])) ?></span>
          </div>
          <h4 class="font-bold leading-snug line-clamp-2 group-hover:text-neon transition"><?= e(post_field($p,'title')) ?></h4>
          <p class="mt-1 text-sm text-slate-400 line-clamp-2"><?= e(post_field($p,'excerpt') ?: mb_substr(strip_tags(post_field($p,'body')),0,120).'…') ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <p id="noResults" class="hidden glass rounded-2xl p-8 text-center text-slate-400 mt-6"><?= e(t('idx.noresults')) ?></p>
<?php endif; ?>

<script>
  /* Мгновенная клиентская фильтрация ленты по строке в шапке */
  const input = document.getElementById('globalSearch');
  if (input) {
    input.addEventListener('input', () => {
      const term = input.value.trim().toLowerCase();
      let visible = 0;
      document.querySelectorAll('.feed-card').forEach(card => {
        const hit = card.dataset.search.includes(term);
        card.style.display = hit ? '' : 'none';
        if (hit) visible++;
      });
      const nr = document.getElementById('noResults');
      if (nr) nr.classList.toggle('hidden', visible !== 0);
    });
  }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
