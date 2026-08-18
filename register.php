<?php
/** register.php — регистрация автора. */
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) redirect('dashboard.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$ok, $error] = register_author($_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($ok) {
        attempt_login($_POST['email'], $_POST['password']);   // авто-вход
        redirect('dashboard.php');
    }
}

$pageTitle = t('nav.become_author') . ' — 11x11';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-md">
  <div class="glass rounded-2xl p-8 shadow-glow">
    <h1 class="text-2xl font-extrabold text-center"><?= e(t('reg.title_a')) ?><span class="text-neon"><?= e(t('reg.title_neon')) ?></span></h1>
    <p class="text-center text-sm text-slate-400 mt-1"><?= e(t('reg.sub')) ?></p>

    <?php if ($error): ?>
      <div class="mt-5 rounded-lg bg-rose-500/15 ring-1 ring-rose-400/30 text-rose-300 px-4 py-3 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4">
      <?= csrf_field() ?>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.name')) ?></label>
        <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.email')) ?></label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <div>
        <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.password')) ?> <span class="text-slate-500"><?= e(t('reg.pass_hint')) ?></span></label>
        <input type="password" name="password" required
               class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon focus:ring-2 focus:ring-neon/30 transition">
      </div>
      <button class="w-full rounded-xl bg-neon text-pitch-900 font-semibold py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('reg.btn')) ?></button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-400">
      <?= e(t('reg.have_acc')) ?> <a href="<?= base_url('login.php') ?>" class="text-neon hover:underline"><?= e(t('login.btn')) ?></a>
    </p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
