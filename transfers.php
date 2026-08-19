<?php
/**
 * transfers.php — трансферный рынок.
 * Все видят таблицу; админ/владелец могут добавлять и удалять записи.
 */
require_once __DIR__ . '/includes/auth.php';

$canManage = has_role('admin','owner');

if ($canManage && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';

    if ($do === 'add') {
        $player = trim($_POST['player'] ?? '');
        $from   = trim($_POST['from_club'] ?? '');
        $to     = trim($_POST['to_club'] ?? '');
        $fee    = (float)($_POST['fee'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['rumour','negotiation','done','failed'], true) ? $_POST['status'] : 'rumour';

        if ($player && $from && $to) {
            db()->prepare(
                "INSERT INTO transfers (player,from_club,to_club,fee,status) VALUES (?,?,?,?,?)"
            )->execute([$player,$from,$to,$fee,$status]);
        }
    } elseif ($do === 'delete') {
        db()->prepare("DELETE FROM transfers WHERE id=?")->execute([(int)($_POST['id'] ?? 0)]);
    }
    redirect('transfers.php');
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = db()->prepare("SELECT * FROM transfers WHERE player LIKE ? OR from_club LIKE ? OR to_club LIKE ? ORDER BY created_at DESC");
    $like = "%$q%";
    $stmt->execute([$like,$like,$like]);
} else {
    $stmt = db()->query("SELECT * FROM transfers ORDER BY created_at DESC");
}
$rows = $stmt->fetchAll();

$pageTitle = t('nav.transfers') . ' — 11x11';
require __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-between flex-wrap gap-4 mb-8">
  <div>
    <h1 class="text-3xl font-extrabold inline-flex items-center gap-2"><span class="text-neon"><?= icon('swap', 'w-7 h-7') ?></span><?= e(t('trs.title')) ?></h1>
    <p class="text-slate-400 mt-1"><?= e(t('trs.sub')) ?></p>
  </div>
  <form method="get" class="relative">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('trs.search')) ?>"
           class="rounded-xl bg-pitch-900/60 border border-white/10 pl-10 pr-4 py-2.5 text-sm outline-none focus:border-neon w-64">
    <svg class="absolute left-3 top-3 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.3-4.3M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
  </form>
</div>

<?php if ($canManage): ?>
  <div class="glass rounded-2xl p-6 mb-8">
    <h2 class="font-bold mb-4"><?= e(t('trs.add')) ?></h2>
    <form method="post" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="add">
      <div class="lg:col-span-2"><label class="block text-xs text-slate-400 mb-1"><?= e(t('trs.player')) ?></label>
        <input name="player" required class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon"></div>
      <div><label class="block text-xs text-slate-400 mb-1"><?= e(t('trs.from')) ?></label>
        <input name="from_club" required class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon"></div>
      <div><label class="block text-xs text-slate-400 mb-1"><?= e(t('trs.to')) ?></label>
        <input name="to_club" required class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon"></div>
      <div><label class="block text-xs text-slate-400 mb-1"><?= e(t('trs.fee')) ?></label>
        <input name="fee" type="number" step="0.1" min="0" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon"></div>
      <div><label class="block text-xs text-slate-400 mb-1"><?= e(t('trs.status')) ?></label>
        <select name="status" class="w-full rounded-lg bg-pitch-900/60 border border-white/10 px-3 py-2 text-sm outline-none focus:border-neon">
          <option value="rumour"><?= e(t('tr.rumour')) ?></option><option value="negotiation"><?= e(t('tr.negotiation')) ?></option>
          <option value="done"><?= e(t('tr.done')) ?></option><option value="failed"><?= e(t('tr.failed')) ?></option>
        </select></div>
      <div class="sm:col-span-2 lg:col-span-6">
        <button class="rounded-xl bg-neon text-pitch-900 font-semibold px-5 py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('trs.add_btn')) ?></button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="glass rounded-2xl overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="text-left text-slate-400 border-b border-white/10">
        <tr>
          <th class="px-5 py-3 font-medium"><?= e(t('trs.player')) ?></th>
          <th class="px-5 py-3 font-medium"><?= e(t('trs.from')) ?></th>
          <th class="px-5 py-3 font-medium"><?= e(t('trs.to')) ?></th>
          <th class="px-5 py-3 font-medium text-right"><?= e(t('trs.th_fee')) ?></th>
          <th class="px-5 py-3 font-medium"><?= e(t('trs.status')) ?></th>
          <?php if ($canManage): ?><th class="px-5 py-3 font-medium text-right"></th><?php endif; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-white/5">
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400"><?= e(t('trs.empty')) ?><?= $q ? e(t('trs.empty_q')) : '' ?>.</td></tr>
        <?php else: foreach ($rows as $t): ?>
          <tr class="hover:bg-white/5 transition">
            <td class="px-5 py-3 font-semibold"><?= e($t['player']) ?></td>
            <td class="px-5 py-3 text-slate-300"><?= e($t['from_club']) ?></td>
            <td class="px-5 py-3 text-neon font-medium"><?= e($t['to_club']) ?></td>
            <td class="px-5 py-3 text-right tabular-nums"><?= $t['fee'] > 0 ? '€' . number_format((float)$t['fee'], 1, '.', ' ') . ' ' . e(t('trs.mln')) : '—' ?></td>
            <td class="px-5 py-3"><?= transfer_badge($t['status']) ?></td>
            <?php if ($canManage): ?>
              <td class="px-5 py-3 text-right">
                <form method="post" class="inline" onsubmit="return confirm('<?= e(t('trs.confirm_del')) ?>')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="do" value="delete">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button class="rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 px-3 py-1.5 transition inline-flex items-center"><?= icon('x', 'w-4 h-4') ?></button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
