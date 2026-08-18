<?php
/**
 * includes/categories.php — категории из БД (двуязычные, с иконками).
 * Владелец управляет ими в admin/categories.php.
 */

/** Все категории (кэш на запрос), упорядочены. */
function all_categories(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    foreach (db()->query("SELECT * FROM categories ORDER BY sort_order, name_en") as $row) {
        $cache[$row['slug']] = $row;
    }
    return $cache;
}

/** Список slug'ов по порядку (для форм). */
function category_keys(): array
{
    return array_keys(all_categories());
}

/** Двуязычная подпись категории по slug. */
function category_label(string $slug): string
{
    $c = all_categories()[$slug] ?? null;
    if (!$c) return $slug;
    return lang() === 'en' ? $c['name_en'] : $c['name_ru'];
}

/** Иконка категории (эмодзи). */
function category_icon(string $slug): string
{
    return all_categories()[$slug]['icon'] ?? '⚽';
}

/** Подпись с иконкой: "🇪🇸 Ла Лига". */
function category_badge_text(string $slug): string
{
    $icon = category_icon($slug);
    return trim($icon . ' ' . category_label($slug));
}

/**
 * Авто-подбор категории по тексту (заголовок+тело) на основе keywords.
 * Возвращает slug наиболее подходящей категории или 'news'.
 */
function detect_category(string $text): string
{
    $text = mb_strtolower($text);
    foreach (all_categories() as $slug => $c) {
        if ($slug === 'news') continue;                       // «Новости» — запасной вариант
        $kw = trim((string)$c['keywords']);
        if ($kw === '') continue;
        foreach (explode('|', $kw) as $word) {
            $word = trim(mb_strtolower($word));
            if ($word !== '' && mb_strpos($text, $word) !== false) {
                return $slug;
            }
        }
    }
    return 'news';
}
