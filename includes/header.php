<?php
/**
 * includes/header.php — общая «шапка», навигация, стили.
 * Ожидает необязательную переменную $pageTitle.
 */
require_once __DIR__ . '/auth.php';
$u    = current_user();
$role = current_role();
$title = $pageTitle ?? '11x11 — футбольный портал';
?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?></title>

  <!-- Tailwind CSS (Play CDN — для локального XAMPP этого достаточно) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            pitch:  { 900:'#031c11', 800:'#052e16', 700:'#064e3b', 600:'#065f46' },
            neon:   { DEFAULT:'#a3e635', 400:'#bef264', 500:'#84cc16' },
          },
          fontFamily: { display: ['Manrope','system-ui','sans-serif'] },
          boxShadow:  { glow: '0 0 40px -8px rgba(163,230,53,.35)' },
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family:'Manrope',sans-serif; }
    /* Глубокий градиент футбольного поля */
    .bg-stadium {
      background:
        radial-gradient(1200px 600px at 80% -10%, rgba(163,230,53,.10), transparent 60%),
        radial-gradient(900px 500px at 0% 0%, rgba(6,95,70,.35), transparent 55%),
        linear-gradient(180deg,#031c11 0%,#04240f 100%);
    }
    /* Стеклморфизм */
    .glass {
      background: rgba(6,46,22,.55);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(163,230,53,.14);
    }
    .glass-hover:hover { border-color: rgba(163,230,53,.45); box-shadow: 0 0 40px -8px rgba(163,230,53,.35); }
    ::selection { background:#a3e635; color:#031c11; }
    /* Кастомный скроллбар */
    ::-webkit-scrollbar { width:10px; }
    ::-webkit-scrollbar-track { background:#031c11; }
    ::-webkit-scrollbar-thumb { background:#065f46; border-radius:10px; }
  </style>
</head>
<body class="bg-stadium min-h-screen text-slate-100 antialiased">

<!-- ======================= NAVBAR ======================= -->
<header class="sticky top-0 z-40">
  <nav class="glass">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between gap-4">

        <!-- Лого: если положить свой assets/logo.png — покажется он, иначе SVG-версия -->
        <a href="<?= base_url('index.php') ?>" class="flex items-center shrink-0" aria-label="11x11">
          <?php if (is_file(__DIR__ . '/../assets/logo.png')): ?>
            <img src="<?= base_url('assets/logo.png') ?>" alt="11x11" class="h-14 sm:h-16 w-auto max-w-[150px] sm:max-w-[240px] object-contain object-left">
          <?php else: ?>
            <img src="<?= base_url('assets/logo.svg') ?>" alt="11x11" class="h-14 sm:h-16 w-auto max-w-[240px] object-contain object-left">
          <?php endif; ?>
        </a>

        <!-- Поиск (мгновенная фильтрация на главной) -->
        <form action="<?= base_url('index.php') ?>" method="get"
              class="hidden md:flex flex-1 max-w-md items-center">
          <div class="relative w-full">
            <input id="globalSearch" type="search" name="q"
                   value="<?= e($_GET['q'] ?? '') ?>"
                   placeholder="<?= e(t('search.placeholder')) ?>"
                   class="w-full rounded-xl bg-pitch-900/60 border border-white/10 pl-10 pr-4 py-2 text-sm
                          placeholder:text-slate-400 focus:border-neon focus:ring-2 focus:ring-neon/30 outline-none transition">
            <svg class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.3-4.3M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
          </div>
        </form>

        <!-- Меню по ролям -->
        <div class="flex items-center gap-1 sm:gap-2">
          <a href="<?= base_url('index.php') ?>"    class="hidden sm:inline px-3 py-2 text-sm rounded-lg hover:bg-white/5 transition"><?= e(t('nav.feed')) ?></a>
          <a href="<?= base_url('transfers.php') ?>" class="hidden sm:inline px-3 py-2 text-sm rounded-lg hover:bg-white/5 transition"><?= e(t('nav.transfers')) ?></a>

          <?php if (is_logged_in()): ?>
            <?php if (has_role('author','admin','owner')): ?>
              <a href="<?= base_url('posts/my_posts.php') ?>" class="px-3 py-2 text-sm rounded-lg hover:bg-white/5 transition"><?= e(t('nav.my_posts')) ?></a>
            <?php endif; ?>

            <?php if (has_role('admin','owner')): ?>
              <a href="<?= base_url('admin/moderation.php') ?>"
                 class="px-3 py-2 text-sm rounded-lg text-amber-300 hover:bg-amber-400/10 transition"><?= e(t('nav.moderation')) ?></a>
            <?php endif; ?>

            <?php if (has_role('owner')): ?>
              <a href="<?= base_url('admin/categories.php') ?>"
                 class="px-3 py-2 text-sm rounded-lg hover:bg-white/5 transition"><?= e(t('nav.categories')) ?></a>
              <a href="<?= base_url('admin/users.php') ?>"
                 class="px-3 py-2 text-sm rounded-lg text-neon hover:bg-neon/10 transition"><?= e(t('nav.access')) ?></a>
            <?php endif; ?>

            <!-- Профиль -->
            <div class="flex items-center gap-2 pl-2 ml-1 border-l border-white/10">
              <div class="hidden sm:block text-right leading-tight">
                <div class="text-sm font-semibold"><?= e($u['name']) ?></div>
                <div class="text-[11px] text-neon"><?= e(role_label($role)) ?></div>
              </div>
              <a href="<?= base_url('logout.php') ?>"
                 class="grid h-9 w-9 place-items-center rounded-lg bg-white/5 hover:bg-rose-500/20 hover:text-rose-300 transition" title="<?= e(t('nav.logout')) ?>">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
              </a>
            </div>
          <?php else: ?>
            <a href="<?= base_url('login.php') ?>"    class="px-3 py-2 text-sm rounded-lg hover:bg-white/5 transition"><?= e(t('nav.login')) ?></a>
            <a href="<?= base_url('register.php') ?>"
               class="px-4 py-2 text-sm font-semibold rounded-lg bg-neon text-pitch-900 hover:bg-neon-400 shadow-glow transition"><?= e(t('nav.become_author')) ?></a>
          <?php endif; ?>

          <!-- Переключатель языка RU / EN -->
          <div class="flex items-center rounded-lg bg-white/5 ml-1 text-xs font-semibold overflow-hidden">
            <a href="<?= e(lang_switch_url('ru')) ?>" class="px-2.5 py-1.5 transition <?= lang()==='ru' ? 'bg-neon text-pitch-900' : 'text-slate-300 hover:bg-white/10' ?>">RU</a>
            <a href="<?= e(lang_switch_url('en')) ?>" class="px-2.5 py-1.5 transition <?= lang()==='en' ? 'bg-neon text-pitch-900' : 'text-slate-300 hover:bg-white/10' ?>">EN</a>
          </div>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
