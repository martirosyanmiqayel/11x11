  </main>

<!-- ======================= FOOTER ======================= -->
<footer class="mt-16 border-t border-white/10">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-400">
    <div class="flex items-center gap-2">
      <?php if (is_file(__DIR__ . '/../assets/logo.png')): ?>
        <img src="<?= base_url('assets/logo.png') ?>" alt="11x11" class="h-8 w-8 object-contain rounded-lg">
      <?php else: ?>
        <img src="<?= base_url('assets/logo.svg') ?>" alt="11x11" class="h-6 w-auto">
      <?php endif; ?>
      <span>© <?= date('Y') ?> — <?= e(t('footer.tagline')) ?></span>
    </div>
    <div class="flex gap-4">
      <a href="<?= base_url('index.php') ?>" class="hover:text-neon transition"><?= e(t('nav.feed')) ?></a>
      <a href="<?= base_url('transfers.php') ?>" class="hover:text-neon transition"><?= e(t('nav.transfers')) ?></a>
      <a href="#" class="hover:text-neon transition"><?= e(t('footer.about')) ?></a>
    </div>
  </div>
</footer>
</body>
</html>
