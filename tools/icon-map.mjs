/**
 * Semantic icon map — the single source of truth for bundled icon packs.
 *
 * Every icon has a pack-agnostic *semantic key* (e.g. "cart"). Each pack maps
 * that key to its own file name, so switching the active pack re-skins every
 * button without touching per-item settings. `null` means the pack has no good
 * equivalent; at runtime the plugin falls back to the pack's `fallback` pack.
 *
 * Run `node tools/build-icons.mjs` after editing this file.
 */

/**
 * Pack definitions.
 * - `outline(name)` / `filled(name)` return the file path inside the npm package.
 * - `filled` is optional; when present the "filled icon on active item" option works.
 * - `fallback` is the pack used for keys this pack lacks (e.g. brand logos).
 */
export const PACKS = {
  lucide: {
    label: 'Lucide',
    npm: 'lucide-static',
    version: '1.47.0',
    license: 'ISC',
    outline: (n) => `icons/${n}.svg`,
    filled: null,
    fallback: 'tabler',
  },
  tabler: {
    label: 'Tabler',
    npm: '@tabler/icons',
    version: '3.48.0',
    license: 'MIT',
    outline: (n) => `icons/outline/${n}.svg`,
    filled: (n) => `icons/filled/${n}.svg`,
    fallback: null,
  },
  phosphor: {
    label: 'Phosphor',
    npm: '@phosphor-icons/core',
    version: '2.1.1',
    license: 'MIT',
    outline: (n) => `assets/regular/${n}.svg`,
    filled: (n) => `assets/fill/${n}-fill.svg`,
    fallback: 'tabler',
  },
  'phosphor-duotone': {
    label: 'Phosphor Duotone',
    npm: '@phosphor-icons/core',
    version: '2.1.1',
    license: 'MIT',
    outline: (n) => `assets/duotone/${n}-duotone.svg`,
    filled: (n) => `assets/fill/${n}-fill.svg`,
    fallback: 'tabler',
    // Duotone shares Phosphor's names.
    namesFrom: 'phosphor',
  },
  heroicons: {
    label: 'Heroicons',
    npm: 'heroicons',
    version: '2.2.0',
    license: 'MIT',
    outline: (n) => `24/outline/${n}.svg`,
    filled: (n) => `24/solid/${n}.svg`,
    fallback: 'tabler',
  },
  bootstrap: {
    label: 'Bootstrap Icons',
    npm: 'bootstrap-icons',
    version: '1.13.1',
    license: 'MIT',
    outline: (n) => `icons/${n}.svg`,
    filled: (n) => `icons/${n}-fill.svg`,
    fallback: 'tabler',
  },
};

/**
 * Semantic icons.
 * `label` is the Persian name shown in the admin picker; `keywords` feed its search.
 * `fa` is the Font Awesome 5 Pro class shipped by the Studiare theme (weight prefix
 * is added at render time; brand icons carry their own `fab` prefix).
 */
export const ICONS = [
  { key: 'home', label: 'خانه', keywords: 'home house خانه', fa: 'fa-home', names: { lucide: 'house', tabler: 'home', phosphor: 'house', heroicons: 'home', bootstrap: 'house' } },
  { key: 'search', label: 'جستجو', keywords: 'search find جستجو', fa: 'fa-search', names: { lucide: 'search', tabler: 'search', phosphor: 'magnifying-glass', heroicons: 'magnifying-glass', bootstrap: 'search' } },
  { key: 'cart', label: 'سبد خرید', keywords: 'cart shop سبد خرید', fa: 'fa-shopping-cart', names: { lucide: 'shopping-cart', tabler: 'shopping-cart', phosphor: 'shopping-cart-simple', heroicons: 'shopping-cart', bootstrap: 'cart' } },
  { key: 'bag', label: 'کیف خرید', keywords: 'bag shopping کیف', fa: 'fa-shopping-bag', names: { lucide: 'shopping-bag', tabler: 'shopping-bag', phosphor: 'shopping-bag', heroicons: 'shopping-bag', bootstrap: 'bag' } },
  { key: 'basket', label: 'سبد', keywords: 'basket سبد', fa: 'fa-shopping-basket', names: { lucide: 'shopping-basket', tabler: 'basket', phosphor: 'basket', heroicons: null, bootstrap: 'basket' } },
  { key: 'user', label: 'کاربر', keywords: 'user account profile کاربر حساب', fa: 'fa-user', names: { lucide: 'user', tabler: 'user', phosphor: 'user', heroicons: 'user', bootstrap: 'person' } },
  { key: 'user-circle', label: 'پروفایل', keywords: 'user circle profile پروفایل', fa: 'fa-user-circle', names: { lucide: 'circle-user', tabler: 'user-circle', phosphor: 'user-circle', heroicons: 'user-circle', bootstrap: 'person-circle' } },
  { key: 'users', label: 'کاربران', keywords: 'users team community کاربران', fa: 'fa-users', names: { lucide: 'users', tabler: 'users', phosphor: 'users', heroicons: 'users', bootstrap: 'people' } },
  { key: 'login', label: 'ورود', keywords: 'login sign in ورود', fa: 'fa-sign-in', names: { lucide: 'log-in', tabler: 'login', phosphor: 'sign-in', heroicons: 'arrow-right-end-on-rectangle', bootstrap: 'box-arrow-in-right' } },
  { key: 'logout', label: 'خروج', keywords: 'logout sign out خروج', fa: 'fa-sign-out', names: { lucide: 'log-out', tabler: 'logout', phosphor: 'sign-out', heroicons: 'arrow-right-start-on-rectangle', bootstrap: 'box-arrow-right' } },
  { key: 'menu', label: 'منو', keywords: 'menu hamburger منو', fa: 'fa-bars', names: { lucide: 'menu', tabler: 'menu-2', phosphor: 'list', heroicons: 'bars-3', bootstrap: 'list' } },
  { key: 'grid', label: 'دسته‌ها', keywords: 'grid apps categories دسته', fa: 'fa-th-large', names: { lucide: 'layout-grid', tabler: 'layout-grid', phosphor: 'squares-four', heroicons: 'squares-2x2', bootstrap: 'grid' } },
  { key: 'more', label: 'بیشتر', keywords: 'more dots ellipsis بیشتر', fa: 'fa-ellipsis-h', names: { lucide: 'ellipsis', tabler: 'dots', phosphor: 'dots-three', heroicons: 'ellipsis-horizontal', bootstrap: 'three-dots' } },
  { key: 'more-vertical', label: 'بیشتر (عمودی)', keywords: 'more vertical dots بیشتر', fa: 'fa-ellipsis-v', names: { lucide: 'ellipsis-vertical', tabler: 'dots-vertical', phosphor: 'dots-three-vertical', heroicons: 'ellipsis-vertical', bootstrap: 'three-dots-vertical' } },
  { key: 'arrow-up', label: 'بالا', keywords: 'arrow up top بالا', fa: 'fa-arrow-up', names: { lucide: 'arrow-up', tabler: 'arrow-up', phosphor: 'arrow-up', heroicons: 'arrow-up', bootstrap: 'arrow-up' } },
  { key: 'chevron-up', label: 'فلش بالا', keywords: 'chevron caret up فلش', fa: 'fa-chevron-up', names: { lucide: 'chevron-up', tabler: 'chevron-up', phosphor: 'caret-up', heroicons: 'chevron-up', bootstrap: 'chevron-up' } },
  { key: 'heart', label: 'علاقه‌مندی', keywords: 'heart wishlist favorite علاقه', fa: 'fa-heart', names: { lucide: 'heart', tabler: 'heart', phosphor: 'heart', heroicons: 'heart', bootstrap: 'heart' } },
  { key: 'bell', label: 'اعلان', keywords: 'bell notification اعلان', fa: 'fa-bell', names: { lucide: 'bell', tabler: 'bell', phosphor: 'bell', heroicons: 'bell', bootstrap: 'bell' } },
  { key: 'book', label: 'کتاب', keywords: 'book course کتاب دوره', fa: 'fa-book', names: { lucide: 'book', tabler: 'notebook', phosphor: 'book', heroicons: 'book-open', bootstrap: 'book' } },
  { key: 'book-open', label: 'کتاب باز', keywords: 'book open lesson درس', fa: 'fa-book-open', names: { lucide: 'book-open', tabler: 'book', phosphor: 'book-open', heroicons: 'book-open', bootstrap: 'book-half' } },
  { key: 'graduation', label: 'آموزش', keywords: 'graduation school academy آموزش دوره', fa: 'fa-graduation-cap', names: { lucide: 'graduation-cap', tabler: 'school', phosphor: 'graduation-cap', heroicons: 'academic-cap', bootstrap: 'mortarboard' } },
  { key: 'play', label: 'پخش', keywords: 'play video course پخش', fa: 'fa-play-circle', names: { lucide: 'circle-play', tabler: 'player-play', phosphor: 'play-circle', heroicons: 'play-circle', bootstrap: 'play-circle' } },
  { key: 'video', label: 'ویدیو', keywords: 'video camera ویدیو', fa: 'fa-video', names: { lucide: 'video', tabler: 'video', phosphor: 'video-camera', heroicons: 'video-camera', bootstrap: 'camera-video' } },
  { key: 'headphones', label: 'پادکست', keywords: 'headphones podcast audio پادکست', fa: 'fa-headphones', names: { lucide: 'headphones', tabler: 'headphones', phosphor: 'headphones', heroicons: null, bootstrap: 'headphones' } },
  { key: 'chat', label: 'گفتگو', keywords: 'chat message comment گفتگو پیام', fa: 'fa-comment', names: { lucide: 'message-circle', tabler: 'message-circle', phosphor: 'chat-circle', heroicons: 'chat-bubble-oval-left', bootstrap: 'chat' } },
  { key: 'support', label: 'پشتیبانی', keywords: 'support headset help پشتیبانی', fa: 'fa-headset', names: { lucide: 'headset', tabler: 'headset', phosphor: 'headset', heroicons: null, bootstrap: 'headset' } },
  { key: 'phone', label: 'تماس', keywords: 'phone call تماس تلفن', fa: 'fa-phone', names: { lucide: 'phone', tabler: 'phone', phosphor: 'phone', heroicons: 'phone', bootstrap: 'telephone' } },
  { key: 'mail', label: 'ایمیل', keywords: 'mail email envelope ایمیل', fa: 'fa-envelope', names: { lucide: 'mail', tabler: 'mail', phosphor: 'envelope', heroicons: 'envelope', bootstrap: 'envelope' } },
  { key: 'whatsapp', label: 'واتساپ', keywords: 'whatsapp واتساپ', fa: 'fab fa-whatsapp', names: { lucide: null, tabler: 'brand-whatsapp', phosphor: 'whatsapp-logo', heroicons: null, bootstrap: 'whatsapp' } },
  { key: 'telegram', label: 'تلگرام', keywords: 'telegram تلگرام', fa: 'fab fa-telegram-plane', names: { lucide: null, tabler: 'brand-telegram', phosphor: 'telegram-logo', heroicons: null, bootstrap: 'telegram' } },
  { key: 'instagram', label: 'اینستاگرام', keywords: 'instagram اینستاگرام', fa: 'fab fa-instagram', names: { lucide: null, tabler: 'brand-instagram', phosphor: 'instagram-logo', heroicons: null, bootstrap: 'instagram' } },
  { key: 'x', label: 'ایکس (توییتر)', keywords: 'x twitter توییتر ایکس', fa: 'fab fa-x-twitter', names: { lucide: null, tabler: 'brand-x', phosphor: 'x-logo', heroicons: null, bootstrap: 'twitter-x' } },
  { key: 'linkedin', label: 'لینکدین', keywords: 'linkedin لینکدین', fa: 'fab fa-linkedin', names: { lucide: null, tabler: 'brand-linkedin', phosphor: 'linkedin-logo', heroicons: null, bootstrap: 'linkedin' } },
  { key: 'bookmark', label: 'نشان', keywords: 'bookmark save نشان', fa: 'fa-bookmark', names: { lucide: 'bookmark', tabler: 'bookmark', phosphor: 'bookmark-simple', heroicons: 'bookmark', bootstrap: 'bookmark' } },
  { key: 'settings', label: 'تنظیمات', keywords: 'settings gear cog تنظیمات', fa: 'fa-cog', names: { lucide: 'settings', tabler: 'settings', phosphor: 'gear', heroicons: 'cog-6-tooth', bootstrap: 'gear' } },
  { key: 'moon', label: 'ماه (تاریک)', keywords: 'moon dark night تاریک', fa: 'fa-moon', names: { lucide: 'moon', tabler: 'moon', phosphor: 'moon', heroicons: 'moon', bootstrap: 'moon' } },
  { key: 'sun', label: 'خورشید (روشن)', keywords: 'sun light روشن', fa: 'fa-sun', names: { lucide: 'sun', tabler: 'sun', phosphor: 'sun', heroicons: 'sun', bootstrap: 'sun' } },
  { key: 'category', label: 'لایه‌ها', keywords: 'category layers stack لایه دسته', fa: 'fa-layer-group', names: { lucide: 'layers', tabler: 'stack-2', phosphor: 'stack', heroicons: 'rectangle-stack', bootstrap: 'layers' } },
  { key: 'compass', label: 'کاوش', keywords: 'compass explore کاوش', fa: 'fa-compass', names: { lucide: 'compass', tabler: 'compass', phosphor: 'compass', heroicons: null, bootstrap: 'compass' } },
  { key: 'fire', label: 'داغ', keywords: 'fire hot trending داغ پرطرفدار', fa: 'fa-fire', names: { lucide: 'flame', tabler: 'flame', phosphor: 'fire', heroicons: 'fire', bootstrap: 'fire' } },
  { key: 'star', label: 'ستاره', keywords: 'star rating ستاره', fa: 'fa-star', names: { lucide: 'star', tabler: 'star', phosphor: 'star', heroicons: 'star', bootstrap: 'star' } },
  { key: 'gift', label: 'هدیه', keywords: 'gift present هدیه', fa: 'fa-gift', names: { lucide: 'gift', tabler: 'gift', phosphor: 'gift', heroicons: 'gift', bootstrap: 'gift' } },
  { key: 'tag', label: 'برچسب', keywords: 'tag label برچسب', fa: 'fa-tag', names: { lucide: 'tag', tabler: 'tag', phosphor: 'tag', heroicons: 'tag', bootstrap: 'tag' } },
  { key: 'discount', label: 'تخفیف', keywords: 'discount percent sale تخفیف', fa: 'fa-percent', names: { lucide: 'percent', tabler: 'discount', phosphor: 'percent', heroicons: 'receipt-percent', bootstrap: 'percent' } },
  { key: 'wallet', label: 'کیف پول', keywords: 'wallet money کیف پول', fa: 'fa-wallet', names: { lucide: 'wallet', tabler: 'wallet', phosphor: 'wallet', heroicons: 'wallet', bootstrap: 'wallet' } },
  { key: 'calendar', label: 'تقویم', keywords: 'calendar event تقویم رویداد', fa: 'fa-calendar-alt', names: { lucide: 'calendar', tabler: 'calendar', phosphor: 'calendar', heroicons: 'calendar', bootstrap: 'calendar' } },
  { key: 'clock', label: 'زمان', keywords: 'clock time زمان', fa: 'fa-clock', names: { lucide: 'clock', tabler: 'clock', phosphor: 'clock', heroicons: 'clock', bootstrap: 'clock' } },
  { key: 'download', label: 'دانلود', keywords: 'download دانلود', fa: 'fa-download', names: { lucide: 'download', tabler: 'download', phosphor: 'download-simple', heroicons: 'arrow-down-tray', bootstrap: 'download' } },
  { key: 'plus', label: 'افزودن', keywords: 'plus add افزودن', fa: 'fa-plus', names: { lucide: 'plus', tabler: 'plus', phosphor: 'plus', heroicons: 'plus', bootstrap: 'plus-lg' } },
  { key: 'info', label: 'اطلاعات', keywords: 'info about درباره اطلاعات', fa: 'fa-info-circle', names: { lucide: 'info', tabler: 'info-circle', phosphor: 'info', heroicons: 'information-circle', bootstrap: 'info-circle' } },
  { key: 'help', label: 'راهنما', keywords: 'help question faq راهنما سوال', fa: 'fa-question-circle', names: { lucide: 'circle-question-mark', tabler: 'help-circle', phosphor: 'question', heroicons: 'question-mark-circle', bootstrap: 'question-circle' } },
  { key: 'map-pin', label: 'آدرس', keywords: 'map pin location آدرس نقشه', fa: 'fa-map-marker-alt', names: { lucide: 'map-pin', tabler: 'map-pin', phosphor: 'map-pin', heroicons: 'map-pin', bootstrap: 'geo-alt' } },
  { key: 'ticket', label: 'تیکت', keywords: 'ticket support تیکت', fa: 'fa-ticket-alt', names: { lucide: 'ticket', tabler: 'ticket', phosphor: 'ticket', heroicons: 'ticket', bootstrap: 'ticket' } },
  { key: 'trophy', label: 'جایزه', keywords: 'trophy award جایزه', fa: 'fa-trophy', names: { lucide: 'trophy', tabler: 'trophy', phosphor: 'trophy', heroicons: 'trophy', bootstrap: 'trophy' } },
  { key: 'award', label: 'مدرک', keywords: 'award certificate badge مدرک گواهی', fa: 'fa-award', names: { lucide: 'award', tabler: 'award', phosphor: 'medal', heroicons: null, bootstrap: 'award' } },
  { key: 'notes', label: 'مقاله', keywords: 'notes article file document مقاله', fa: 'fa-file-alt', names: { lucide: 'file-text', tabler: 'file-text', phosphor: 'file-text', heroicons: 'document-text', bootstrap: 'file-text' } },
  { key: 'news', label: 'وبلاگ', keywords: 'news blog newspaper وبلاگ اخبار', fa: 'fa-newspaper', names: { lucide: 'newspaper', tabler: 'news', phosphor: 'newspaper', heroicons: 'newspaper', bootstrap: 'newspaper' } },
  { key: 'list', label: 'فهرست', keywords: 'list items فهرست', fa: 'fa-list-ul', names: { lucide: 'list', tabler: 'list', phosphor: 'list-bullets', heroicons: 'list-bullet', bootstrap: 'list-ul' } },
  { key: 'chart', label: 'آمار', keywords: 'chart stats dashboard آمار', fa: 'fa-chart-bar', names: { lucide: 'chart-column', tabler: 'chart-bar', phosphor: 'chart-bar', heroicons: 'chart-bar', bootstrap: 'bar-chart' } },
  { key: 'store', label: 'فروشگاه', keywords: 'store shop فروشگاه', fa: 'fa-store', names: { lucide: 'store', tabler: 'building-store', phosphor: 'storefront', heroicons: 'building-storefront', bootstrap: 'shop' } },
  { key: 'share', label: 'اشتراک‌گذاری', keywords: 'share اشتراک', fa: 'fa-share-alt', names: { lucide: 'share-2', tabler: 'share', phosphor: 'share-network', heroicons: 'share', bootstrap: 'share' } },
  { key: 'lock', label: 'قفل', keywords: 'lock secure قفل', fa: 'fa-lock', names: { lucide: 'lock', tabler: 'lock', phosphor: 'lock', heroicons: 'lock-closed', bootstrap: 'lock' } },
  { key: 'sparkles', label: 'ویژه', keywords: 'sparkles special new ویژه جدید', fa: 'fa-sparkles', names: { lucide: 'sparkles', tabler: 'sparkles', phosphor: 'sparkle', heroicons: 'sparkles', bootstrap: 'stars' } },
  { key: 'close', label: 'بستن', keywords: 'close x بستن', fa: 'fa-times', names: { lucide: 'x', tabler: 'x', phosphor: 'x', heroicons: 'x-mark', bootstrap: 'x-lg' } },
  { key: 'check', label: 'تیک', keywords: 'check done تیک', fa: 'fa-check', names: { lucide: 'check', tabler: 'check', phosphor: 'check', heroicons: 'check', bootstrap: 'check-lg' } },
  { key: 'eye', label: 'نمایش', keywords: 'eye view نمایش', fa: 'fa-eye', names: { lucide: 'eye', tabler: 'eye', phosphor: 'eye', heroicons: 'eye', bootstrap: 'eye' } },
  { key: 'image', label: 'تصویر', keywords: 'image photo gallery تصویر', fa: 'fa-image', names: { lucide: 'image', tabler: 'photo', phosphor: 'image', heroicons: 'photo', bootstrap: 'image' } },
  { key: 'code', label: 'کد', keywords: 'code developer کد', fa: 'fa-code', names: { lucide: 'code', tabler: 'code', phosphor: 'code', heroicons: 'code-bracket', bootstrap: 'code-slash' } },
  { key: 'palette', label: 'رنگ', keywords: 'palette color رنگ', fa: 'fa-palette', names: { lucide: 'palette', tabler: 'palette', phosphor: 'palette', heroicons: 'swatch', bootstrap: 'palette' } },
  { key: 'sliders', label: 'تنظیم', keywords: 'sliders adjust تنظیم', fa: 'fa-sliders-h', names: { lucide: 'sliders-horizontal', tabler: 'adjustments-horizontal', phosphor: 'sliders-horizontal', heroicons: 'adjustments-horizontal', bootstrap: 'sliders' } },
  { key: 'mobile', label: 'موبایل', keywords: 'mobile phone device موبایل', fa: 'fa-mobile-alt', names: { lucide: 'smartphone', tabler: 'device-mobile', phosphor: 'device-mobile', heroicons: 'device-phone-mobile', bootstrap: 'phone' } },
  { key: 'copy', label: 'کپی', keywords: 'copy duplicate کپی', fa: 'fa-copy', names: { lucide: 'copy', tabler: 'copy', phosphor: 'copy', heroicons: 'document-duplicate', bootstrap: 'copy' } },
  { key: 'trash', label: 'حذف', keywords: 'trash delete حذف', fa: 'fa-trash-alt', names: { lucide: 'trash-2', tabler: 'trash', phosphor: 'trash', heroicons: 'trash', bootstrap: 'trash' } },
  { key: 'grip', label: 'جابجایی', keywords: 'grip drag move جابجایی', fa: 'fa-grip-vertical', names: { lucide: 'grip-vertical', tabler: 'grip-vertical', phosphor: 'dots-six-vertical', heroicons: null, bootstrap: 'grip-vertical' } },
  { key: 'chevron-down', label: 'فلش پایین', keywords: 'chevron caret down فلش', fa: 'fa-chevron-down', names: { lucide: 'chevron-down', tabler: 'chevron-down', phosphor: 'caret-down', heroicons: 'chevron-down', bootstrap: 'chevron-down' } },
];
