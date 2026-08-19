<?php
/**
 * includes/icons.php — инлайновые SVG-иконки (вместо эмодзи).
 * Использование: <?= icon('shield', 'w-5 h-5') ?>
 * Иконки в стиле Feather: currentColor, наследуют цвет текста.
 */
function icon(string $name, string $class = 'w-5 h-5'): string
{
    $p = [
        // мяч / контент по умолчанию
        'ball'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7.2l4.3 3.1-1.6 5.1H9.3L7.7 10.3z"/><path d="M12 3v4.2M4.2 9.6l3.5 2.6M6.9 18.3l2.4-3.4M17.1 18.3l-2.4-3.4M19.8 9.6l-3.5 2.6"/>',
        // редактировать
        'edit'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        // просмотры
        'eye'     => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        // модерация
        'shield'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        // владелец
        'crown'   => '<path d="M3 8l4.5 4L12 5l4.5 7L21 8l-1.5 11h-15z"/>',
        // категории / метка
        'tag'     => '<path d="M20.6 13.4 12 22l-9-9V3h10z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
        // трансферы (две стрелки)
        'swap'    => '<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        // сохранить черновик
        'save'    => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
        // отправить на проверку
        'send'    => '<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
        // опубликовать / молния
        'zap'     => '<path d="M13 2 3 14h7l-1 8 10-12h-7z"/>',
        // одобрить / галочка
        'check'   => '<path d="M20 6 9 17l-5-5"/>',
        // авто-подбор
        'sparkles'=> '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
        // посты / документ
        'doc'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        // управление доступом / пользователи
        'users'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        // закрыть / удалить
        'x'       => '<path d="M18 6 6 18M6 6l12 12"/>',
        // внешняя ссылка
        'external'=> '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/>',
        // предупреждение (отклонён)
        'alert'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
    ];
    $body = $p[$name] ?? '';
    return '<svg class="' . htmlspecialchars($class, ENT_QUOTES) . '" viewBox="0 0 24 24" fill="none" '
         . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
         . 'aria-hidden="true">' . $body . '</svg>';
}
