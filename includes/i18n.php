<?php
/**
 * includes/i18n.php — двуязычность RU / EN.
 * Язык выбирается: ?lang=en|ru  →  cookie  →  по умолчанию 'ru'.
 * Использование в шаблонах:  <?= t('key') ?>  или  t('key', ['n'=>5]).
 */

/** Определяет и запоминает текущий язык (до вывода — можно ставить cookie). */
function init_lang(): string
{
    $allowed = ['ru', 'en'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        setcookie('lang', $_GET['lang'], time() + 31536000, '/');   // на год
        return $GLOBALS['LANG_CODE'] = $_GET['lang'];
    }
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowed, true)) {
        return $GLOBALS['LANG_CODE'] = $_COOKIE['lang'];
    }
    return $GLOBALS['LANG_CODE'] = 'en';   // язык сайта по умолчанию — английский
}

function lang(): string
{
    return $GLOBALS['LANG_CODE'] ?? 'ru';
}

/** Перевод по ключу с подстановкой {placeholders}. */
function t(string $key, array $repl = []): string
{
    $l   = lang();
    $str = $GLOBALS['TR'][$l][$key] ?? $GLOBALS['TR']['ru'][$key] ?? $key;
    foreach ($repl as $k => $v) {
        $str = str_replace('{' . $k . '}', (string) $v, $str);
    }
    return $str;
}

/**
 * Возвращает поле поста на текущем языке с запасным вариантом.
 * $base — 'title' | 'excerpt' | 'body'. Для EN берёт *_en, для RU — базовое;
 * если нужного перевода нет, показывает второй язык (чтобы не было пустоты).
 */
function post_field(array $post, string $base): string
{
    $en = trim((string)($post[$base . '_en'] ?? ''));
    $ru = trim((string)($post[$base] ?? ''));
    if (lang() === 'en') return $en !== '' ? $en : $ru;
    return $ru !== '' ? $ru : $en;
}

/** Ссылка на ту же страницу со сменой языка. */
function lang_switch_url(string $code): string
{
    $qs = $_GET;
    $qs['lang'] = $code;
    return strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($qs);
}

/* =========================================================
 *  СЛОВАРЬ
 * ========================================================= */
$GLOBALS['TR'] = [

/* ------------------------- RU ------------------------- */
'ru' => [
  // nav / общий
  'nav.feed'=>'Лента', 'nav.transfers'=>'Трансферы', 'nav.my_posts'=>'Мои посты',
  'nav.moderation'=>'Модерация', 'nav.access'=>'Управление доступом', 'nav.categories'=>'Категории',
  'cats.title'=>'🏷️ Категории', 'cats.sub'=>'Добавляйте и редактируйте категории новостей (двуязычные, с иконками).',
  'cats.new'=>'＋ Новая категория', 'cats.slug'=>'Slug (лат.)', 'cats.name_ru'=>'Название (RU)',
  'cats.name_en'=>'Название (EN)', 'cats.icon'=>'Иконка', 'cats.keywords'=>'Ключевые слова',
  'cats.kw_hint'=>'через | — для авто-подбора категории по тексту поста', 'cats.order'=>'Порядок',
  'cats.save'=>'Сохранить', 'cats.add'=>'Добавить', 'cats.delete'=>'Удалить', 'cats.posts'=>'постов',
  'cats.confirm_del'=>'Удалить категорию? Посты в ней станут без категории.',
  'cats.created'=>'Категория добавлена.', 'cats.updated'=>'Категория обновлена.', 'cats.deleted'=>'Категория удалена.',
  'cats.slug_bad'=>'Slug: только латинские буквы, цифры, дефис.', 'cats.slug_exists'=>'Такой slug уже есть.',
  'nav.login'=>'Войти', 'nav.become_author'=>'Стать автором', 'nav.logout'=>'Выход',
  'search.placeholder'=>'Поиск новостей, клубов, игроков…',
  'role.owner'=>'Владелец', 'role.admin'=>'Администратор', 'role.author'=>'Автор',
  'footer.tagline'=>'премиальный футбольный портал', 'footer.about'=>'О проекте',
  'st.draft'=>'Черновик', 'st.pending'=>'На проверке', 'st.published'=>'Опубликован', 'st.rejected'=>'Отклонён',
  'tr.rumour'=>'Слух', 'tr.negotiation'=>'Переговоры', 'tr.done'=>'Завершён', 'tr.failed'=>'Сорвался',
  // Категории (ключ → русская подпись)
  'cat.news'=>'Новости', 'cat.transfers'=>'Трансферы', 'cat.laliga'=>'Ла Лига',
  'cat.epl'=>'АПЛ', 'cat.ucl'=>'Лига чемпионов', 'cat.seriea'=>'Серия А',
  'cat.ligue1'=>'Лига 1', 'cat.armenia'=>'Армянский футбол', 'cat.national'=>'Сборные',

  // index
  'idx.badge'=>'⚡ Живая лента футбола',
  'idx.h1a'=>'Всё о футболе — ', 'idx.h1neon'=>'быстро', 'idx.h1b'=>', глубоко и по делу.',
  'idx.sub'=>'Новости, аналитика и трансферы от авторов сообщества 11×11.',
  'idx.search'=>'Поиск…', 'idx.all'=>'Все',
  'idx.results'=>'Результаты по запросу «{q}»: найдено {n}',
  'idx.empty'=>'Пока нет публикаций', 'idx.empty_q'=>' по этому запросу',
  'idx.noresults'=>'Ничего не найдено.',
  'idx.latest'=>'Свежие новости', 'idx.read'=>'Читать', 'idx.source'=>'Источник',

  // post
  'post.notfound_title'=>'Не найдено', 'post.notfound'=>'Статья не найдена.',
  'post.back'=>'← Назад к ленте',
  'post.preview'=>'Предпросмотр — виден только вам и модераторам',
  'post.author'=>'Автор:', 'post.edit'=>'✏️ Редактировать',

  // login
  'login.title_a'=>'Вход в ', 'login.sub'=>'Рады видеть снова',
  'login.err'=>'Неверный email или пароль.',
  'f.email'=>'Email', 'f.password'=>'Пароль', 'login.btn'=>'Войти',
  'login.no_acc'=>'Нет аккаунта?',

  // register
  'reg.title_a'=>'Стать ', 'reg.title_neon'=>'автором',
  'reg.sub'=>'Пишите о футболе для тысяч болельщиков',
  'f.name'=>'Имя', 'reg.pass_hint'=>'(мин. 6 символов)', 'reg.btn'=>'Создать аккаунт',
  'reg.have_acc'=>'Уже есть аккаунт?',
  'err.name_short'=>'Слишком короткое имя.', 'err.email_bad'=>'Некорректный email.',
  'err.pass_short'=>'Пароль минимум 6 символов.', 'err.email_taken'=>'Пользователь с таким email уже существует.',

  // dashboard
  'dash.hi'=>'Привет, ', 'dash.role_is'=>'Ваша роль:', 'dash.new_post'=>'＋ Новый пост',
  'dash.users'=>'Пользователей', 'dash.on_mod'=>'На модерации', 'dash.published'=>'Опубликовано', 'dash.transfers'=>'Трансферов',
  'dash.drafts'=>'Черновиков', 'dash.pending'=>'На проверке', 'dash.rejected'=>'Отклонено',
  'dash.card_myposts'=>'Мои посты', 'dash.card_myposts_d'=>'Черновики, статьи на проверке и публикации.',
  'dash.card_mod'=>'Модерация', 'dash.card_mod_d'=>'Одобряйте или отклоняйте посты авторов.',
  'dash.card_access'=>'Управление доступом', 'dash.card_access_d'=>'Роли, администраторы, права пользователей.',
  'dash.card_tr'=>'Трансферы', 'dash.card_tr_d'=>'Свежий трансферный рынок.',

  // post form
  'form.back'=>'← К моим постам', 'form.new'=>'Новый пост', 'form.edit'=>'Редактирование поста',
  'form.sub'=>'Сохраните черновик или сразу отправьте на модерацию.',
  'form.title'=>'Заголовок', 'form.category'=>'Категория',
  'form.cover'=>'URL обложки', 'form.optional'=>'(необязательно)',
  'form.excerpt'=>'Краткий анонс', 'form.excerpt_hint'=>'(до 300 символов)',
  'form.body'=>'Текст статьи', 'form.body_hint'=>'Разделяйте абзацы пустой строкой.',
  'form.save_draft'=>'💾 Сохранить черновик', 'form.send_review'=>'📤 Отправить на проверку',
  'form.publish_now'=>'🚀 Опубликовать сразу', 'form.req'=>'Заголовок и текст обязательны.',
  'form.auto'=>'подобрано автоматически',
  'form.ru_section'=>'🇷🇺 Русская версия', 'form.en_section'=>'🇬🇧 English version',
  'form.req_both'=>'Заполните заголовок и текст на обоих языках (RU и EN).',
  'form.bilingual_hint'=>'Пост публикуется на двух языках — заполните обе версии.',

  // my_posts
  'mp.all'=>'Все посты', 'mp.mine'=>'Мои посты',
  'mp.empty'=>'Постов пока нет.', 'mp.create_first'=>'Создать первый',
  'mp.th_title'=>'Заголовок', 'mp.th_author'=>'Автор', 'mp.th_cat'=>'Категория',
  'mp.th_status'=>'Статус', 'mp.th_upd'=>'Обновлён', 'mp.th_act'=>'Действия',
  'mp.edit'=>'Изменить', 'mp.submit'=>'На проверку', 'mp.delete'=>'Удалить',
  'mp.confirm_del'=>'Удалить пост безвозвратно?',

  // moderation
  'mod.title'=>'🛡️ Модерация', 'mod.queue'=>'{n} в очереди',
  'mod.empty'=>'Очередь пуста — все посты обработаны.',
  'mod.preview'=>'Открыть предпросмотр ↗',
  'mod.approve'=>'✅ Одобрить и опубликовать',
  'mod.reason'=>'Причина отклонения', 'mod.reject'=>'Отклонить',
  'mod.confirm_reject'=>'Отклонить пост?', 'mod.default_reason'=>'Не соответствует требованиям.',

  // users
  'usr.title'=>'👑 Управление доступом',
  'usr.sub'=>'Создавайте администраторов и авторов, назначайте роли и управляйте доступом.',
  'usr.new'=>'＋ Новый пользователь', 'usr.role'=>'Роль', 'usr.create'=>'Создать',
  'usr.th_user'=>'Пользователь', 'usr.th_role'=>'Роль', 'usr.th_access'=>'Доступ', 'usr.th_act'=>'Действия',
  'usr.you'=>'(вы)', 'usr.active'=>'● Активен', 'usr.blocked'=>'● Заблокирован',
  'usr.block'=>'Заблокировать', 'usr.unblock'=>'Разблокировать', 'usr.delete'=>'Удалить',
  'usr.confirm_del'=>'Удалить пользователя и все его посты?',
  'usr.owner_badge'=>'👑 Владелец',
  'usr.created'=>'Пользователь создан: {e}', 'usr.taken'=>'Email уже занят.',
  'usr.bad_fields'=>'Проверьте поля: имя, корректный email, пароль ≥ 6 символов.',
  'usr.role_upd'=>'Роль обновлена.', 'usr.access_upd'=>'Статус доступа изменён.',
  'usr.deleted'=>'Пользователь удалён.', 'usr.owner_protected'=>'Роль «Владелец» защищена от изменений.',

  // transfers
  'trs.title'=>'🔁 Трансферный рынок', 'trs.sub'=>'Слухи, переговоры и завершённые сделки.',
  'trs.search'=>'Игрок или клуб…', 'trs.add'=>'＋ Добавить трансфер',
  'trs.player'=>'Игрок', 'trs.from'=>'Откуда', 'trs.to'=>'Куда',
  'trs.fee'=>'Сумма (млн €)', 'trs.status'=>'Статус', 'trs.add_btn'=>'Добавить',
  'trs.th_fee'=>'Сумма', 'trs.empty'=>'Нет трансферов', 'trs.empty_q'=>' по запросу',
  'trs.confirm_del'=>'Удалить запись?', 'trs.mln'=>'млн',
],

/* ------------------------- EN ------------------------- */
'en' => [
  'nav.feed'=>'Feed', 'nav.transfers'=>'Transfers', 'nav.my_posts'=>'My Posts',
  'nav.moderation'=>'Moderation', 'nav.access'=>'Access Management', 'nav.categories'=>'Categories',
  'cats.title'=>'🏷️ Categories', 'cats.sub'=>'Add and edit news categories (bilingual, with icons).',
  'cats.new'=>'＋ New category', 'cats.slug'=>'Slug (latin)', 'cats.name_ru'=>'Name (RU)',
  'cats.name_en'=>'Name (EN)', 'cats.icon'=>'Icon', 'cats.keywords'=>'Keywords',
  'cats.kw_hint'=>'separated by | — for auto-detecting the category from post text', 'cats.order'=>'Order',
  'cats.save'=>'Save', 'cats.add'=>'Add', 'cats.delete'=>'Delete', 'cats.posts'=>'posts',
  'cats.confirm_del'=>'Delete this category? Its posts will become uncategorized.',
  'cats.created'=>'Category added.', 'cats.updated'=>'Category updated.', 'cats.deleted'=>'Category deleted.',
  'cats.slug_bad'=>'Slug: latin letters, digits, hyphen only.', 'cats.slug_exists'=>'This slug already exists.',
  'nav.login'=>'Log in', 'nav.become_author'=>'Become an author', 'nav.logout'=>'Log out',
  'search.placeholder'=>'Search news, clubs, players…',
  'role.owner'=>'Owner', 'role.admin'=>'Administrator', 'role.author'=>'Author',
  'footer.tagline'=>'premium football portal', 'footer.about'=>'About',
  'st.draft'=>'Draft', 'st.pending'=>'In review', 'st.published'=>'Published', 'st.rejected'=>'Rejected',
  'tr.rumour'=>'Rumour', 'tr.negotiation'=>'Negotiation', 'tr.done'=>'Done', 'tr.failed'=>'Failed',
  // Categories (key → English label)
  'cat.news'=>'News', 'cat.transfers'=>'Transfers', 'cat.laliga'=>'La Liga',
  'cat.epl'=>'Premier League', 'cat.ucl'=>'Champions League', 'cat.seriea'=>'Serie A',
  'cat.ligue1'=>'Ligue 1', 'cat.armenia'=>'Armenian Football', 'cat.national'=>'National Teams',

  'idx.badge'=>'⚡ Live football feed',
  'idx.h1a'=>'Everything about football — ', 'idx.h1neon'=>'fast', 'idx.h1b'=>', deep and to the point.',
  'idx.sub'=>'News, analysis and transfers from the 11×11 community.',
  'idx.search'=>'Search…', 'idx.all'=>'All',
  'idx.results'=>'Results for “{q}”: {n} found',
  'idx.empty'=>'No publications yet', 'idx.empty_q'=>' for this query',
  'idx.noresults'=>'Nothing found.',
  'idx.latest'=>'Latest news', 'idx.read'=>'Read', 'idx.source'=>'Source',

  'post.notfound_title'=>'Not found', 'post.notfound'=>'Article not found.',
  'post.back'=>'← Back to feed',
  'post.preview'=>'Preview — visible only to you and moderators',
  'post.author'=>'Author:', 'post.edit'=>'✏️ Edit',

  'login.title_a'=>'Log in to ', 'login.sub'=>'Glad to see you again',
  'login.err'=>'Wrong email or password.',
  'f.email'=>'Email', 'f.password'=>'Password', 'login.btn'=>'Log in',
  'login.no_acc'=>'No account yet?',

  'reg.title_a'=>'Become an ', 'reg.title_neon'=>'author',
  'reg.sub'=>'Write about football for thousands of fans',
  'f.name'=>'Name', 'reg.pass_hint'=>'(min. 6 characters)', 'reg.btn'=>'Create account',
  'reg.have_acc'=>'Already have an account?',
  'err.name_short'=>'Name is too short.', 'err.email_bad'=>'Invalid email.',
  'err.pass_short'=>'Password must be at least 6 characters.', 'err.email_taken'=>'A user with this email already exists.',

  'dash.hi'=>'Hi, ', 'dash.role_is'=>'Your role:', 'dash.new_post'=>'＋ New post',
  'dash.users'=>'Users', 'dash.on_mod'=>'In moderation', 'dash.published'=>'Published', 'dash.transfers'=>'Transfers',
  'dash.drafts'=>'Drafts', 'dash.pending'=>'In review', 'dash.rejected'=>'Rejected',
  'dash.card_myposts'=>'My Posts', 'dash.card_myposts_d'=>'Drafts, articles in review and publications.',
  'dash.card_mod'=>'Moderation', 'dash.card_mod_d'=>'Approve or reject authors’ posts.',
  'dash.card_access'=>'Access Management', 'dash.card_access_d'=>'Roles, administrators, user permissions.',
  'dash.card_tr'=>'Transfers', 'dash.card_tr_d'=>'Fresh transfer market.',

  'form.back'=>'← Back to my posts', 'form.new'=>'New post', 'form.edit'=>'Edit post',
  'form.sub'=>'Save a draft or send it straight to moderation.',
  'form.title'=>'Title', 'form.category'=>'Category',
  'form.cover'=>'Cover URL', 'form.optional'=>'(optional)',
  'form.excerpt'=>'Short excerpt', 'form.excerpt_hint'=>'(up to 300 characters)',
  'form.body'=>'Article text', 'form.body_hint'=>'Separate paragraphs with a blank line.',
  'form.save_draft'=>'💾 Save draft', 'form.send_review'=>'📤 Send for review',
  'form.publish_now'=>'🚀 Publish now', 'form.req'=>'Title and text are required.',
  'form.auto'=>'auto-detected',
  'form.ru_section'=>'🇷🇺 Russian version', 'form.en_section'=>'🇬🇧 English version',
  'form.req_both'=>'Fill in the title and text in both languages (RU and EN).',
  'form.bilingual_hint'=>'The post is published in two languages — fill in both versions.',

  'mp.all'=>'All posts', 'mp.mine'=>'My Posts',
  'mp.empty'=>'No posts yet.', 'mp.create_first'=>'Create the first one',
  'mp.th_title'=>'Title', 'mp.th_author'=>'Author', 'mp.th_cat'=>'Category',
  'mp.th_status'=>'Status', 'mp.th_upd'=>'Updated', 'mp.th_act'=>'Actions',
  'mp.edit'=>'Edit', 'mp.submit'=>'Send for review', 'mp.delete'=>'Delete',
  'mp.confirm_del'=>'Delete this post permanently?',

  'mod.title'=>'🛡️ Moderation', 'mod.queue'=>'{n} in queue',
  'mod.empty'=>'Queue is empty — all posts processed.',
  'mod.preview'=>'Open preview ↗',
  'mod.approve'=>'✅ Approve & publish',
  'mod.reason'=>'Rejection reason', 'mod.reject'=>'Reject',
  'mod.confirm_reject'=>'Reject this post?', 'mod.default_reason'=>'Does not meet the requirements.',

  'usr.title'=>'👑 Access Management',
  'usr.sub'=>'Create administrators and authors, assign roles and manage access.',
  'usr.new'=>'＋ New user', 'usr.role'=>'Role', 'usr.create'=>'Create',
  'usr.th_user'=>'User', 'usr.th_role'=>'Role', 'usr.th_access'=>'Access', 'usr.th_act'=>'Actions',
  'usr.you'=>'(you)', 'usr.active'=>'● Active', 'usr.blocked'=>'● Blocked',
  'usr.block'=>'Block', 'usr.unblock'=>'Unblock', 'usr.delete'=>'Delete',
  'usr.confirm_del'=>'Delete the user and all their posts?',
  'usr.owner_badge'=>'👑 Owner',
  'usr.created'=>'User created: {e}', 'usr.taken'=>'Email already taken.',
  'usr.bad_fields'=>'Check the fields: name, valid email, password ≥ 6 characters.',
  'usr.role_upd'=>'Role updated.', 'usr.access_upd'=>'Access status changed.',
  'usr.deleted'=>'User deleted.', 'usr.owner_protected'=>'The “Owner” role is protected from changes.',

  'trs.title'=>'🔁 Transfer market', 'trs.sub'=>'Rumours, negotiations and completed deals.',
  'trs.search'=>'Player or club…', 'trs.add'=>'＋ Add transfer',
  'trs.player'=>'Player', 'trs.from'=>'From', 'trs.to'=>'To',
  'trs.fee'=>'Fee (€M)', 'trs.status'=>'Status', 'trs.add_btn'=>'Add',
  'trs.th_fee'=>'Fee', 'trs.empty'=>'No transfers', 'trs.empty_q'=>' for this query',
  'trs.confirm_del'=>'Delete this record?', 'trs.mln'=>'M',
],

];
