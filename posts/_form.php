<?php
/**
 * posts/_form.php — общая форма создания/редактирования.
 * ДВУЯЗЫЧНАЯ: автор заполняет пост на русском И английском.
 * Ожидает: $error, $categories, (опц.) $post для режима редактирования.
 */
$isEdit = isset($post);
$val = fn(string $k, $d = '') => e($_POST[$k] ?? $d);
?>
<div class="mx-auto max-w-3xl">
  <a href="<?= base_url('posts/my_posts.php') ?>" class="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-neon transition mb-6"><?= e(t('form.back')) ?></a>

  <h1 class="text-3xl font-extrabold mb-2"><?= $isEdit ? e(t('form.edit')) : e(t('form.new')) ?></h1>
  <p class="text-slate-400 mb-6"><?= e(t('form.bilingual_hint')) ?></p>

  <?php if (!empty($error)): ?>
    <div class="mb-5 rounded-lg bg-rose-500/15 ring-1 ring-rose-400/30 text-rose-300 px-4 py-3 text-sm"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" class="glass rounded-2xl p-6 sm:p-8 space-y-6">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$post['id'] ?>"><?php endif; ?>

    <!-- Общие поля (не зависят от языка) -->
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="flex items-center gap-2 text-sm text-slate-300 mb-1">
          <?= e(t('form.category')) ?>
          <span id="autoCatBadge" class="hidden text-[11px] rounded-full bg-neon/15 text-neon px-2 py-0.5 inline-flex items-center gap-1"><?= icon('sparkles', 'w-3 h-3') ?><?= e(t('form.auto')) ?></span>
        </label>
        <select id="catSelect" name="category"
                class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon transition">
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c) ?>" <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>><?= e(category_label($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.cover')) ?> <span class="text-slate-500"><?= e(t('form.optional')) ?></span></label>
        <input type="url" name="cover_url" value="<?= $val('cover_url') ?>" placeholder="https://…"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
    </div>

    <!-- ==================== РУССКАЯ ВЕРСИЯ ==================== -->
    <fieldset class="rounded-2xl border border-white/10 p-5 space-y-4">
      <legend class="px-2 text-sm font-bold text-neon"><?= e(t('form.ru_section')) ?></legend>

      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.title')) ?> *</label>
        <input type="text" name="title" required value="<?= $val('title') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.excerpt')) ?> <span class="text-slate-500"><?= e(t('form.excerpt_hint')) ?></span></label>
        <input type="text" name="excerpt" maxlength="300" value="<?= $val('excerpt') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.body')) ?> *</label>
        <textarea name="body" rows="10" required
                  class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition"><?= $val('body') ?></textarea>
      </div>
    </fieldset>

    <!-- ==================== ENGLISH VERSION ==================== -->
    <fieldset class="rounded-2xl border border-white/10 p-5 space-y-4">
      <legend class="px-2 text-sm font-bold text-neon"><?= e(t('form.en_section')) ?></legend>

      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.title')) ?> (EN) *</label>
        <input type="text" name="title_en" required value="<?= $val('title_en') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.excerpt')) ?> (EN) <span class="text-slate-500"><?= e(t('form.excerpt_hint')) ?></span></label>
        <input type="text" name="excerpt_en" maxlength="300" value="<?= $val('excerpt_en') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('form.body')) ?> (EN) *</label>
        <textarea name="body_en" rows="10" required
                  class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition"><?= $val('body_en') ?></textarea>
        <p class="text-xs text-slate-500 mt-1"><?= e(t('form.body_hint')) ?></p>
      </div>
    </fieldset>

    <div class="flex flex-wrap gap-3 pt-1">
      <button type="submit" name="action" value="draft"
              class="inline-flex items-center gap-2 rounded-xl bg-white/5 hover:bg-white/10 px-5 py-2.5 font-semibold transition"><?= icon('save', 'w-4 h-4') ?><?= e(t('form.save_draft')) ?></button>

      <button type="submit" name="action" value="pending"
              class="inline-flex items-center gap-2 rounded-xl bg-amber-400/90 text-pitch-900 hover:bg-amber-300 px-5 py-2.5 font-semibold transition"><?= icon('send', 'w-4 h-4') ?><?= e(t('form.send_review')) ?></button>

      <?php if (has_role('admin','owner')): ?>
        <button type="submit" name="action" value="publish"
                class="inline-flex items-center gap-2 rounded-xl bg-neon text-pitch-900 hover:bg-neon-400 px-5 py-2.5 font-semibold shadow-glow transition"><?= icon('zap', 'w-4 h-4') ?><?= e(t('form.publish_now')) ?></button>
      <?php endif; ?>
    </div>
  </form>
</div>

<script>
/* Авто-подбор категории по тексту заголовка/статьи (пока автор не выбрал вручную) */
(function(){
  const cats = <?= json_encode(array_map(fn($c)=>['slug'=>$c['slug'],'kw'=>$c['keywords']], all_categories()), JSON_UNESCAPED_UNICODE) ?>;
  const sel   = document.getElementById('catSelect');
  const badge = document.getElementById('autoCatBadge');
  const fields = ['title','title_en','body','body_en'].map(n=>document.querySelector(`[name="${n}"]`)).filter(Boolean);
  <?php $isEdit = isset($post); ?>
  let manual = <?= $isEdit ? 'true' : 'false' ?>;   // при редактировании не перебиваем существующую

  function detect(){
    const text = fields.map(f=>f.value).join(' ').toLowerCase();
    for (const c of cats){
      if (c.slug === 'news' || !c.kw) continue;
      for (const w of c.kw.split('|')){
        const word = w.trim().toLowerCase();
        if (word && text.includes(word)) return c.slug;
      }
    }
    return 'news';
  }
  function apply(){
    if (manual) return;
    const slug = detect();
    if (slug && sel.value !== slug){ sel.value = slug; }
    badge.classList.toggle('hidden', !fields.some(f=>f.value.trim()));
  }
  sel.addEventListener('change', ()=>{ manual = true; badge.classList.add('hidden'); });
  fields.forEach(f=>f.addEventListener('input', apply));
  apply();
})();
</script>
