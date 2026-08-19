<?php
/**
 * admin/users.php — панель управления пользователями и правами.
 * Доступ: ТОЛЬКО владелец (checkAccess('owner')).
 * Возможности: создать администратора/автора, сменить роль,
 * заблокировать/активировать, удалить. Роль owner неприкосновенна.
 */
require_once __DIR__ . '/../includes/auth.php';
checkAccess('owner');

$notice = null;
$me = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $do = $_POST['do'] ?? '';

    // --- Создание пользователя владельцем ---
    if ($do === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin','author'], true) ? $_POST['role'] : 'author';

        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 6) {
            $exists = db()->prepare("SELECT id FROM users WHERE email=?");
            $exists->execute([$email]);
            if ($exists->fetchColumn()) {
                $notice = ['err', t('usr.taken')];
            } else {
                $ins = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)");
                $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
                $notice = ['ok', t('usr.created', ['e' => $email])];
            }
        } else {
            $notice = ['err', t('usr.bad_fields')];
        }
    }

    // Действия над существующим пользователем
    $uid = (int)($_POST['uid'] ?? 0);
    if ($uid && $uid !== $me) {
        // Нельзя трогать других владельцев
        $roleOf = db()->prepare("SELECT role FROM users WHERE id=?");
        $roleOf->execute([$uid]);
        $targetRole = $roleOf->fetchColumn();

        if ($targetRole !== 'owner') {
            if ($do === 'role') {
                $new = in_array($_POST['role'] ?? '', ['admin','author'], true) ? $_POST['role'] : 'author';
                db()->prepare("UPDATE users SET role=? WHERE id=?")->execute([$new, $uid]);
                $notice = ['ok', t('usr.role_upd')];
            } elseif ($do === 'toggle') {
                db()->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$uid]);
                $notice = ['ok', t('usr.access_upd')];
            } elseif ($do === 'delete') {
                db()->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
                $notice = ['ok', t('usr.deleted')];
            }
        } else {
            $notice = ['err', t('usr.owner_protected')];
        }
    }
}

$users = db()->query("SELECT * FROM users ORDER BY FIELD(role,'owner','admin','author'), created_at")->fetchAll();

$pageTitle = t('nav.access') . ' — 11x11';
require __DIR__ . '/../includes/header.php';
?>

<h1 class="text-3xl font-extrabold mb-2 inline-flex items-center gap-2"><span class="text-neon"><?= icon('users', 'w-7 h-7') ?></span><?= e(t('usr.title')) ?></h1>
<p class="text-slate-400 mb-8"><?= e(t('usr.sub')) ?></p>

<?php if ($notice): ?>
  <div class="mb-6 rounded-lg px-4 py-3 text-sm <?= $notice[0]==='ok'
        ? 'bg-lime-500/15 ring-1 ring-lime-400/30 text-lime-300'
        : 'bg-rose-500/15 ring-1 ring-rose-400/30 text-rose-300' ?>"><?= e($notice[1]) ?></div>
<?php endif; ?>

<div class="grid gap-8 lg:grid-cols-3">
  <!-- Форма создания -->
  <div class="lg:col-span-1">
    <div class="glass rounded-2xl p-6 sticky top-24">
      <h2 class="font-bold text-lg mb-4"><?= e(t('usr.new')) ?></h2>
      <form method="post" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="create">
        <div>
          <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.name')) ?></label>
          <input type="text" name="name" required class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon transition">
        </div>
        <div>
          <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.email')) ?></label>
          <input type="email" name="email" required class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon transition">
        </div>
        <div>
          <label class="block text-sm text-slate-300 mb-1"><?= e(t('f.password')) ?></label>
          <input type="text" name="password" required minlength="6" class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon transition">
        </div>
        <div>
          <label class="block text-sm text-slate-300 mb-1"><?= e(t('usr.role')) ?></label>
          <select name="role" class="w-full rounded-xl bg-pitch-900/60 border border-white/10 px-4 py-2.5 outline-none focus:border-neon transition">
            <option value="author"><?= e(t('role.author')) ?></option>
            <option value="admin"><?= e(t('role.admin')) ?></option>
          </select>
        </div>
        <button class="w-full rounded-xl bg-neon text-pitch-900 font-semibold py-2.5 hover:bg-neon-400 shadow-glow transition"><?= e(t('usr.create')) ?></button>
      </form>
    </div>
  </div>

  <!-- Таблица пользователей -->
  <div class="lg:col-span-2">
    <div class="glass rounded-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-left text-slate-400 border-b border-white/10">
            <tr>
              <th class="px-5 py-3 font-medium"><?= e(t('usr.th_user')) ?></th>
              <th class="px-5 py-3 font-medium"><?= e(t('usr.th_role')) ?></th>
              <th class="px-5 py-3 font-medium"><?= e(t('usr.th_access')) ?></th>
              <th class="px-5 py-3 font-medium text-right"><?= e(t('usr.th_act')) ?></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <?php foreach ($users as $usr): ?>
              <?php $isOwnerRow = $usr['role']==='owner'; $isSelf = (int)$usr['id']===$me; ?>
              <tr class="hover:bg-white/5 transition">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-neon/20 text-neon font-bold"><?= e(mb_substr($usr['name'],0,1)) ?></span>
                    <div>
                      <div class="font-semibold"><?= e($usr['name']) ?> <?= $isSelf ? '<span class="text-xs text-neon">' . e(t('usr.you')) . '</span>' : '' ?></div>
                      <div class="text-xs text-slate-400"><?= e($usr['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3">
                  <?php if ($isOwnerRow): ?>
                    <span class="inline-flex items-center rounded-full bg-neon/20 text-neon px-2.5 py-0.5 text-xs font-semibold ring-1 ring-neon/40"><?= e(t('usr.owner_badge')) ?></span>
                  <?php else: ?>
                    <form method="post" class="inline-flex items-center gap-1">
                      <?= csrf_field() ?>
                      <input type="hidden" name="do" value="role">
                      <input type="hidden" name="uid" value="<?= $usr['id'] ?>">
                      <select name="role" onchange="this.form.submit()"
                              class="rounded-lg bg-pitch-900/60 border border-white/10 px-2 py-1 text-xs outline-none focus:border-neon">
                        <option value="author" <?= $usr['role']==='author'?'selected':'' ?>><?= e(t('role.author')) ?></option>
                        <option value="admin"  <?= $usr['role']==='admin'?'selected':'' ?>><?= e(t('role.admin')) ?></option>
                      </select>
                    </form>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                  <?php if ($usr['is_active']): ?>
                    <span class="text-lime-300"><?= e(t('usr.active')) ?></span>
                  <?php else: ?>
                    <span class="text-rose-300"><?= e(t('usr.blocked')) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <?php if (!$isOwnerRow && !$isSelf): ?>
                      <form method="post" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="do" value="toggle">
                        <input type="hidden" name="uid" value="<?= $usr['id'] ?>">
                        <button class="rounded-lg bg-white/5 hover:bg-white/10 px-3 py-1.5 transition">
                          <?= $usr['is_active'] ? e(t('usr.block')) : e(t('usr.unblock')) ?>
                        </button>
                      </form>
                      <form method="post" class="inline" onsubmit="return confirm('<?= e(t('usr.confirm_del')) ?>')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="do" value="delete">
                        <input type="hidden" name="uid" value="<?= $usr['id'] ?>">
                        <button class="rounded-lg bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 px-3 py-1.5 transition"><?= e(t('usr.delete')) ?></button>
                      </form>
                    <?php else: ?>
                      <span class="text-xs text-slate-500">—</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
