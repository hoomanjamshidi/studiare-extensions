# Studiare Extensions — project guide

A WordPress plugin that adds features to the **Studiare** LMS theme (v13.4, by Suncode, sold on rtl-theme.com).
Features are added one at a time as **modules**. The first module is the **mobile bottom navigation**.

- The user speaks Persian: reply in Persian unless asked otherwise. Code, comments and source strings are English; Persian comes from the `fa_IR` translation.
- The shipping plugin lives in `studiare-extensions/`. Everything outside it (tools, configs, the theme zip) is for development only.
- `studiare-online-learning-wordpress-theme-update-13.4.zip` is the theme, for reference only. Never edit it, and never ship code copied from it (it is commercial and mostly ionCube-encoded).

## Non-negotiable rules

1. **Clean code.** Small single-purpose classes and functions, descriptive names, no dead code, no duplicated logic. Follow WordPress Coding Standards (`phpcs.xml.dist` must pass with 0 errors and 0 warnings).
2. **Comments explain *why*.** Every file and class has a docblock that says what it is for. Every public method has a docblock. Inline comments cover non-obvious decisions (theme quirks, browser workarounds), never what the next line obviously does.
3. **Follow the theme by default.** Colours, fonts and dark mode come from Studiare's CSS variables, and admins can override them. An empty colour setting means "use the theme's colour".
4. **Never break the site.** Degrade gracefully when Studiare, WooCommerce or Elementor is missing. Real `<a href>` links keep working without JS. The bar is hidden with CSS above the breakpoint (no `wp_is_mobile()`), so it is safe with page caching.
5. **Security.** Every setting passes through `Core\Sanitizer` (schema based). AJAX checks the nonce and `manage_options`. Escape on output. User SVG goes through `Icon_Library::sanitize_svg()` on save and again on output.
6. **RTL first, i18n always.** Use logical CSS properties (`inset-inline-start`, `margin-inline-end`). Every string is wrapped in `__()` and friends with the text domain `studiare-extensions`. JS strings are passed from PHP. PHP regexes on text need the `u` flag: without it `\R` also matches the byte 0x85 inside letters such as «م» and cuts Persian words apart.
7. **Accessibility.** Touch targets of at least 44px, a visible `:focus-visible` ring, `aria-*` on sheets and toggles, `prefers-reduced-motion` respected, and hidden labels kept for screen readers.
8. **Support PHP 7.4 and WordPress 6.0 or newer.** Do not use PHP 8-only syntax (`match`, union types, constructor promotion, nullsafe `?->`).

## Layout

```
studiare-extensions/                 ← the plugin (zip this folder)
├── studiare-extensions.php          bootstrap: constants STUDIARE_EXT_*, PHP check, autoloader, boots Plugin on `init`
├── uninstall.php                    deletes every `studiare_ext_*` option (multisite aware)
├── includes/                        PSR-4: StudiareExt\Foo\Bar_Baz → includes/Foo/Bar_Baz.php
│   ├── Plugin.php                   loads textdomain, instantiates modules (filter `studiare_ext_modules`), boots admin
│   ├── Core/
│   │   ├── Module.php               abstract base: option storage, defaults merge, enable/disable, admin hooks
│   │   ├── Sanitizer.php            schema-driven sanitizer (bool/int/float/enum/color/text/url/svg/group/list …)
│   │   ├── Theme_Bridge.php         read-only Studiare integration (Redux options, palette, fonts, dark mode, native bar removal)
│   │   ├── Icon_Library.php         bundled icon packs + Font Awesome mapping + SVG sanitizer
│   │   ├── Search_Query.php         live search query + result view model (bottom nav and header search)
│   │   ├── Asset.php                front-end asset URL/contents: serves `*.min.*` unless SCRIPT_DEBUG
│   │   ├── Arr.php, Color.php, Site.php   small helpers
│   ├── Admin/
│   │   ├── Admin.php                menu "Studiare+", assets, shared layout, window.stxAdmin data
│   │   ├── Ajax_Controller.php      stx_save_settings / stx_reset_settings / stx_toggle_module
│   │   ├── Fields.php               declarative controls bound by data-stx-bind="dot.path"
│   │   └── views/                   layout.php (shell), dashboard.php
│   ├── Modules/Bottom_Nav/
│   │   ├── Module.php               module definition + admin script data
│   │   ├── Schema.php               defaults + sanitizer schema (settings & items)
│   │   ├── Styles.php               the 5 styles' metadata (classic, floating, notch, bubble, pill)
│   │   ├── Item_Types.php           button types (home, link, search, cart, account, menu, content, back_to_top, dark_mode, selector)
│   │   ├── Item_Resolver.php        saved items → view models for the current request (URLs, current page, icons, sheets)
│   │   ├── Renderer.php             view helpers + sheet bodies
│   │   ├── Style_Vars.php           settings → inline CSS custom properties + breakpoint media queries
│   │   ├── Frontend.php             hooks: enqueue, wp_footer render, body class, WooCommerce cart fragments
│   │   ├── Search_Scope.php         which post types a search button covers (validated at runtime)
│   │   ├── Live_Search.php          admin-ajax endpoint `stx_live_search` for the search sheet's live results
│   │   └── views/                   nav.php, sheet.php, sheet-search.php, search-results.php, admin.php (settings panel)
│   ├── Modules/Support_Button/
│   │   ├── Module.php               module definition + admin script data
│   │   ├── Schema.php               defaults + sanitizer schema (channels keyed by id, `order`)
│   │   ├── Channels.php             channel registry: labels, brand colours, glyphs (Bale/Eitaa inline), url() from ID/number/link
│   │   ├── Frontend.php             hooks: enqueue, wp_footer render, visibility rules, view model
│   │   └── views/                   button.php (front, native <details>), admin.php (settings panel)
│   ├── Modules/Theme_Fixes/         repairs for known Studiare bugs, one switch each (`fixes.<id>`)
│   │   ├── Module.php               module definition; boot() boots each switched-on fix
│   │   ├── Schema.php               defaults (every fix on) + sanitizer schema
│   │   ├── Fix.php                  abstract base: id(), title(), description(), boot(), in_use()
│   │   ├── Fixes.php                registry of fix classes (admin order)
│   │   ├── Otp_Digits.php           mobile login (Studiare Core OTP) accepts Persian digits, +98/0098/9… numbers
│   │   └── views/                   admin.php (settings panel)
│   └── Modules/Builder/             page templates: Elementor course/product pages, headers, footers, home, about and contact pages, the blog
│       ├── Presets/                 ready-made designs written in PHP (El::box/El::w), registered in Catalog.php (Home.php = home pages,
│       │                            About.php / Contact.php = about us and contact us pages, Blog.php = post lists and single posts,
│       │                            Blocks.php = shared layout helpers: band(), section_title(), buttons(), link(), shop_url()…)
│       ├── Library.php              installs/restores presets; maybe_upgrade() refreshes unedited ones on a version change
│       ├── Design_Pages.php         creates real pages from page designs (home, about, contact), front page switch, list for the admin
│       ├── Public_Form.php          base of the public forms: honeypot, per-visitor rate limit, JSON or redirect answer, CSV export
│       ├── Newsletter.php           built-in list behind the Newsletter widget (`stx_subscriber`), CSV export, privacy tools
│       ├── Contact_Messages.php     Contact form handler: `stx_message` posts, email copy, CSV export, privacy tools
│       ├── Contact_Inbox.php        the messages list under Studiare+ (WordPress list table, "new" count in the menu)
│       ├── Header_Footer.php        swaps Studiare's header/footer (output buffering), one slot per device
│       ├── Blog_Pages.php           renders post lists and single posts with a blog template (views/blog.php)
│       ├── Live_Search.php          admin-ajax endpoint `stx_builder_search` for the Search widget
│       ├── Search_Results.php       markup of those live results in the chosen look (`detailed` or `compact`)
│       └── Elementor/               Integration, Parts (shared markup), Cards (product/post cards), Picture (slider/featured image <picture>
│                                    + loading hints), Post_Parts (post content with heading anchors, reading time, share links, archive info),
│                                    Category_Parts (category icon and cover picture, shared by Category grid and Blog categories),
│                                    Widgets/* (Home_Base = home widgets; Blog_Base = blog widgets; Page_Base = about/contact widgets;
│                                    Slider = the fast slider, extends Base)
├── assets/
│   ├── admin/                       admin.css, admin.js (window.STX shell), fonts/Vazirmatn (bundled, OFL)
│   ├── icons/                       catalog.json + packs/*.json (generated, see "Icons")
│   ├── modules/bottom-nav/          bottom-nav.css (front and admin preview), bottom-nav.js (front), bottom-nav-admin.js
│   ├── modules/support-button/      support-button.css (front and admin preview), support-button.js (front), support-button-admin.js
│   ├── modules/theme-fixes/         otp-digits.js (front, loads while Studiare's OTP option is on)
│   └── modules/builder/             tokens.css (derived tokens + dark mode, printed inline), builder.css (widgets, layers),
│                                    builder.js (front + editor), builder-admin.*, home.css / home.js (home widgets only,
│                                    handle `stx-builder-home`), blog.css / blog.js (blog widgets, `stx-builder-blog`),
│                                    pages.css / pages.js (about/contact widgets, `stx-builder-pages`),
│                                    slider.css (inline) / slider.js (Slider widget only, `stx-slider`)
└── languages/                       .pot, fa_IR .po/.mo
tools/                               build-icons.mjs, icon-map.mjs, minify.mjs (writes *.min.css/js), build-zip.sh
phpcs.xml.dist                       WordPress-Extra + PHPCompatibilityWP (7.4+)
```

## Adding a new feature (module)

1. Create `includes/Modules/<Name>/Module.php` that extends `Core\Module`. Implement `id()`, `title()`, `description()`, `icon()`, `defaults()`, `sanitize()`, `boot()` and `render_admin()`.
2. Put settings in a `Schema` class (`defaults()` and `fields()` with the same shape) and sanitize with `Sanitizer::apply()`.
3. Register it in `Plugin::register_modules()` (the default list). The admin menu, dashboard card, save/reset/toggle AJAX and the store all work automatically.
4. Build the panel with `Admin\Fields` (`toggle`, `select`, `segmented`, `range`, `text`, `color`) inside `.stx-card`. Use `data-stx-show-if="path=a|b;!other"` for conditional fields.
5. Settings live in one option, `studiare_ext_<id>`. `Module::normalize()` upgrades old saved data (for example, new keys inside list items).

## Bottom navigation: key contracts

- **Markup contract.** `views/nav.php` (PHP) and `renderNav()` in `bottom-nav-admin.js` produce the same markup, and `resolveItems()` there mirrors `Item_Resolver.php`. The admin preview uses the real `bottom-nav.css`. Change them together.
- **Colour tokens.** CSS resolves `--stx-bn-c-<slot>` from the admin overrides `--stx-bn-l-<slot>` (light) and `--stx-bn-d-<slot>` (dark), falling back to theme variables. Light defaults use `:where()` (zero specificity), so dark rules always win. The slots are listed in `Schema::COLOR_SLOTS`.
- **Layout variables on `<nav>`.** `--stx-bn-count`, `--stx-bn-active` (-1 means none) and `--stx-bn-featured`, plus the `has-active` class. The sliding indicators use `translateX(index * 100% * --stx-bn-dir)`, and `--stx-bn-dir` is -1 in RTL.
- **Live search.** The request sends only the button id and the term; post types come from the saved item, so a request cannot widen the search. No nonce, because the endpoint is public and read-only and a nonce in a cached page would expire. The full results page (`/?s=`) gets `post_type` only when exactly one type is selected, because Studiare's `studiare_customize_search_query` reads it as a single string (an array empties the search).
- **Sheets.** `.stx-sheet` elements come after the nav. Sheets whose links we control (search, menu, cart) push a history entry so the Android Back button closes them. Content sheets (Elementor or shortcode) do not.
- **Adding a button type.** Add it to `Item_Types::all()` (label, default label and icon, and the `fields` shown in the editor), add a `build_<type>()` method in `Item_Resolver`, add a JS action in `bottom-nav.js` if it needs one, add editor fields in `views/admin.php` with `data-item-show-if="type=<type>"`, and add `itemProblem()` rules in the admin JS if the type needs configuration.
- **Adding a style.** Add it to `Styles::all()` and add a `.stx-bn--style-<id>` section in `bottom-nav.css`. Test it with 2, 4, 5 and 7 items, in RTL and dark mode.

## Support button: key contracts

- **Markup contract.** `views/button.php` and `renderWidget()` in `support-button-admin.js` produce the same markup; `channelUrl()` there mirrors `Channels::url()` (the admin shows the resolved link under each field). Change them together.
- **Works without JS.** The menu is a native `<details>`/`<summary>`. support-button.js only adds outside-tap/Esc closing, the closing animation (`is-closing`) and the greeting (once per session, `sessionStorage.stxSupportGreeting`). With one ready channel the button is a plain link to it and no script loads (unless the greeting is on).
- **Channels.** A fixed set keyed by id (`channels.<id>.value` binds directly with `data-stx-bind`); `order` keeps their order and `Schema::complete_order()` adds channels from newer versions. A channel shows only when it is on and `Channels::url()` returns a link. Persian/Arabic digits are converted, and local Iranian mobiles (09…) get 98 for wa.me and t.me. Never call them "کانال" in Persian (that means a Telegram channel): the UI says «راه‌های ارتباطی».
- **Lifting.** Pure CSS through `--stx-sb-lift` (bottom nav: `body.stx-bn-on` + `--stx-bn-space`, back to 0 with `stx-bn-is-hidden`; Studiare's own 70px bar below 480px), `--stx-sb-bar` (`body.sc_add_to_cart_fixed_active`, `:has(.stx-buybar--mobile.is-visible)`) and `--stx-sb-btt` (Studiare's `#back-to-top.visible`, which sits in the inline-end corner). Test a new fixed element in both corners.
- **Brand glyphs.** Telegram/phone/mail use Phosphor fill, WhatsApp/Instagram use Bootstrap; Bale and Eitaa are official glyphs stored in `Channels` (no icon pack has them). Brand colours are darkened just enough for 3:1 contrast with the white glyph.

## Theme fixes: key contracts

- **What belongs here.** Repairs for bugs in Studiare or its Studiare Core plugin, done from the outside (hooks, request data, a small script), never by editing or copying their files. Each fix is a `Fix` subclass listed in `Fixes::CLASSES`; its switch (`fixes.<id>`, on by default, also for fixes added later), admin row and dashboard toggle follow automatically. `in_use()` tells the admin whether the theme feature it repairs is switched on (chip next to the switch). Describe the problem the visitor sees in `description()`, so an admin can switch the fix off once the theme repairs it.
- **Studiare Core's OTP login.** Its handlers are encoded, but its scripts are plain: `studiare-core/libs/suncode_otp_reg_login/js/combined_otp_scripts.js` (handle `combined-otp-scripts`, combined form `#combined-otp-form`) and `otp-registration-scripts.js` (older `#otp-login-form` / `#otp-registration-form`). They load on every page while Redux `otp` is on (the login popup can open anywhere). All fields are named `otp_…` (`otp_phone`, `otp_code`, `otp_reg_phone`, `otp_reg`, `otp_back`); AJAX actions `check_and_send_combined_otp`, `verify_combined_otp`, `otp_send_verification_code`, `otp_validate_otp`, `otp_login_validate_otp`. The combined form rejects anything but `/^[0-9]{11}$/` (then a 09xx prefix list) before sending, and the theme only converts Persian digits in `#otp_code`.
- **Otp_Digits.** otp-digits.js listens on `document` in the capture phase, so it runs before the theme's jQuery handlers and covers popup forms added later: Latin digits while typing (caret kept), the full `normalize()` (+98 / 0098 / 98 / 9… → 09…) on paste (the field has `maxlength="11"`, so the pasted text is cleaned before it is cut), on leaving the field, on Enter (the theme sends from its own keydown handler), on button clicks (restored/autofilled values) and on submit. `Otp_Digits::normalize()` does the same to the `otp_*` fields of those AJAX actions at priority 1, for pages cached before the fix was switched on. Change the two together.

## Page templates (Builder): key contracts

- **Presets ship through upgrades.** Preset posts store a hash of their data (`_stx_preset_hash`). When `STUDIARE_EXT_VERSION` changes, `Library::maybe_upgrade()` rewrites the presets nobody edited and installs new ones; edited ones wait for "Restore original". Bump the version to ship preset changes.
- **Widths.** Use `'boxed' => true` (Elementor's site container width), not a fixed pixel width, so templates line up with the page content.
- **Headers.** Every design has desktop rows (`hide` tablet and mobile) plus `Header::mobile_bar()` (`hide` desktop): one 64px row with 44px icon buttons. Size logos by `height`, because square logos get very tall at a fixed width.
- **Menu drawer tabs.** Nav_Menu's `drawer_tabs` puts a second list (product categories, or a chosen menu: no "automatic" choice, it would repeat the main one) beside the main list as `.stx-tabs--tabs` inside the drawer, so builder.js's tab code switches them. The drawer opens on the tab that lists the current page. Nothing to list → the single list as before. The drawer moves to `<body>`, so `--stx-nav-*` from `.stx-nav` do not reach it: drawer colour controls also target `.stx-drawer[data-owner="{{ID}}"]`.
- **Footers.** Every design carries `stx-foot--center-mobile`, so everything is centred on phones. Link lists sit two per row on phones.
- **Course access.** Use `Theme_Bridge::user_has_course()`. It mirrors Studiare's `inc/studi_lessons.php` (WooCommerce purchase, `studi_has_bought_items() === "true"`, or any Studiare subscription). `studi_has_bought_items()` returns the strings "true" and "false", so never cast its result to bool.
- **Curriculum.** The default is Studiare's own lesson list (`theme` mode) for everyone. Do not restyle Studiare's native elements.
- **Theme quirks.** Open the mini cart after the click finishes (`afterClick`), as in the bottom nav. After replacing the footer, keep the empty `#footer.stx-hf__anchor`: Studiare's inline product-page script calls `$('#footer').offset().top` on every scroll.
- **Colours.** Amber text uses `--stx-accent-ink` (WCAG AA on light and dark), never `--stx-accent-strong`. The gallery widget needs `width: 100%`; without it, its aspect-ratio stage collapses in Elementor's wrapping column containers on phones.
- **Search.** The header Search widget and the bottom nav share `Core\Search_Query`. The request can only choose `scope` (any/product/post) and `limit` (3–10). Page results leave out hub pages (front page, posts page, WooCommerce pages, Studiare's header/footer pages): Elementor saves a plain-text copy of everything they show as `post_content`, so they match almost any term.
- **Sticky header.** `header.sticky_desktop` / `sticky_mobile` (`none`, `always`, `scroll_up` = "Smart") apply only to Studiare+ template slots; the theme header keeps Studiare's own sticky options. builder.js sets `--stx-header-offset` to the header height while the header is on screen, and sticky columns and the section jump bar add it to their `top`. Sticky offsets use `--stx-admin-bar`, not WordPress's variable: it is 0 below 600px, where the admin bar scrolls away with the page.
- **Live results look.** `options.search_results` (Header tab) picks `detailed` (the CDesign «نتیجه جستجو» design) or `compact`. Elementor caches the widget's markup, so the widget HTML never depends on the setting: the endpoint returns `style`, and builder.js sets `.stx-live--detailed` from the response. Detailed results carry their own footer, so the widget's static `.stx-live__all` is hidden in that look.

## Slider widget: key contracts

- **Designs.** `Slider::RATIOS` lists them (banner, hero, peek, cards, side, tabs). One markup: `.stx-sl` > `.stx-sl__main` (region) > `.stx-sl__viewport` > `.stx-sl__track` > `.stx-sl__slide` > `.stx-sl__frame` (link or div) > `<picture>`; then `.stx-sl__nav` (dots or tabs + pause); `side` adds `.stx-sl__side`.
- **Works without JS.** The track is a native scroll-snap scroller. slider.js only adds arrows/dots/autoplay, and the fade effect (`is-fade` is added by JS, so without JS slides still swipe).
- **No layout shift.** Every slide box has `aspect-ratio` from the first slide's picture (`--stx-sl-auto-d/-m` inline, phone below 767px), or the "Picture shape" control. Motion uses transform/opacity only; dot/tab progress is `scaleX`, never a width change (autoplay is not user input, so a width change would count as CLS).
- **Loading.** `Picture::html()`: `high` (first slide in "smart" mode: eager + `fetchpriority` + `skip-lazy`/`data-no-lazy` for cache plugins), `eager`, `lazy`. Studiare forces `loading="eager"` on every attachment image (`disable_lazy_load_featured_images`), so Picture re-applies its choice after that filter. `Picture::clean_img_tag()` (on `wp_content_img_tag`) removes the duplicate `loading`/`fetchpriority` that Elementor's image optimizer adds, and WordPress's `sizes="auto, …"`.
- **Slides that start off screen** get `is-later`, and their `<picture>` is `display:none`: Chrome lazy-loads the next slides of a sideways scroller early, which would compete with the LCP picture. slider.js `prepare()` reveals them one slide ahead, only after page load (autoplay) or when the visitor reaches for the slider. `visible_count()` decides how many start visible.
- **Style controls read in render** (`per_view`, `peek_width`, `side_width`) need `'render_type' => 'template'`: Elementor's optimized control loading drops selector-only controls on the front end. Render reads `get_parsed_dynamic_settings()` (keeps settings whose conditions fail).
- **Assets.** Only `stx-slider` (inline CSS on top of the inline `stx-tokens`) + deferred slider.js; no builder.css/js. Scripted jumps add `is-jumping` (turns `scroll-snap-stop: always` off, which Chrome also applies to scripted scrolls).

## Page designs (home, about, contact): key contracts

- **Designs vs pages.** A page design is an `stx_template` whose kind is in `Schema::PAGE_TYPES` (`home`, `about`, `contact`; presets in `Presets/Home.php`, `About.php`, `Contact.php`, upgraded like the others). "Create page" (`Design_Pages::create()`) copies its Elementor data into a new `page` with `_stx_design`, Elementor's `elementor_header_footer` template and Studiare's `_studiare_disable_title` / `_studiare_disable_breadcrumbs`. Editing a page never touches the design. Only home designs offer "Use it as the site's home page". The admin tab is "Pages" (tab id still `home`).
- **Column widths.** Give every column in a preset row an explicit `width`. A container with only `fill` keeps Elementor's 100% basis, so both columns shrink by their basis and the other one ends up far narrower than intended.
- **Widgets.** Home widgets extend `Widgets\Home_Base` (loads `stx-builder-home` CSS/JS only where used). Product and post cards live in `Elementor\Cards`.
- **Grow vs fill.** Elementor's "Grow" also sets `flex-shrink: 0`, so long text pushes a row off screen. In presets use `'fill' => true` (grow and shrink) for text beside actions and for equal cards in a row.
- **Phone collapse.** Chrome measures widgets at zero width inside Elementor's phone containers: aspect-ratio pictures collapse and wrapping rows reserve extra height. Widgets with pictures or wrapping rows need a definite width (the `:is(...)` rule at the top of home.css, like the gallery in builder.css).
- **Swipe rows.** `.stx-swipe` is `position: relative` on purpose: WooCommerce and our cards contain absolute `.screen-reader-text`, which otherwise escapes the scroller and widens the page on phones.
- **Dark mode links.** Studiare paints every link white in dark mode (`body.scdarkcolors a:not(…)`, specificity 0,3,5). Links with their own colour set `--stx-link` next to `color`, and one `body.scdarkcolors a:is(…)` rule per stylesheet restores it. Add new link classes to that list. `<mark>` and `<ins>` need two classes of specificity to drop the theme's highlight.
- **Amber surfaces** (`gradient`, `accent`) keep dark text: white on amber fails contrast.
- **Lazy backgrounds.** Elementor removes `background-image` inside sections until they scroll into view; anything readable on a background image needs a solid `background-color` too.
- **Newsletter.** Public, cached form: no nonce; honeypot `stx_website` and 5 sign-ups per visitor per 15 minutes. Plain posts go to admin-post.php and redirect back with `?stx_news=` and the form anchor.

## Blog: key contracts

- **Kinds.** `archive` (post lists: the posts page, categories, tags, authors, dates, and blog searches when `blog.search` is on) and `post` (single posts). Settings `blog.archive` / `blog.post` hold a template ID or `theme`. `Resolver::blog_kind()` / `blog_template()` decide; `Blog_Pages` renders through `views/blog.php` between the theme header and footer (hides Studiare's title bar with `hide_theme_title`). The wrapper has no `post_class()`: Studiare styles `.post` / `.hentry` boxes.
- **Search.** Only `post_type=post` searches (e.g. the Search widget set to "Blog posts") use the archive template. Other searches list products and pages, which post cards cannot show.
- **Which post.** `Context::post()` is the viewed post; the global post only counts inside a loop (on a post list WordPress sets it to the list's first post); in the editor or preview of a template that is not an `archive`, the newest post with a picture (`sample_post_id()`). Widgets render inside `Blog_Base::with_post()`, which sets the global post.
- **Content and table of contents.** `Post_Parts::content()` runs the content filters once per request, adds ids to H2/H3 from their words (Persian kept) and wraps tables in a scrolling `.stx-prose__table`. The content and the table of contents both read it, so anchors always match. Headings have `scroll-margin-top` for the sticky header.
- **Post lists.** Post grid source `current` lists the main query's posts (`$wp_the_query`; the newest posts while editing) with `paginate_links()`, and titles become H2. "Highlight the first post" only on page 1. The magazine layout sets `--stx-rows` and ends with a `1fr` row so the list stays packed beside the large post. `Post_Grid::DETAILS` says which card styles show which detail; the controls' conditions and the render both use it.
- **Works without JS.** The table of contents is a native `<details>`: designs put an open copy in the sticky sidebar (desktop) and a closed one above the text (phones and tablets) with Elementor's device visibility, so nothing moves after load. Share links are plain links; "Copy link" and the phone share sheet stay `hidden` until blog.js shows them. The reading progress bar moves with `scaleX` only.
- **Comments.** `Post_Comments` prints the theme's own `comments.php`; Studiare already boxes its comment form, so the widget's card is off by default.
- **Class names.** The post title is `.stx-posttitle` (`.stx-ptitle` belongs to Product title). Elementor's `.elementor img { height: auto }` beats one-class image rules: sized images need two classes (`.stx-pimage .stx-pimage__img`).
- **Blog categories** (`Post_Categories`, `stx-post-categories`). Nine looks: navigation (`chips`, `tabs`, `list`, `tree`, `dropdown`) marks the viewed category with `aria-current`; showcase (`cards`, `covers`, `overlay`, `posts`) are `.stx-bcats` grids. Keep the `chips`/`list` values and markup: the blog designs use them. Categories come from one `get_terms()` per request with `pad_counts` (counts include subcategories), sorted by name in the database (its collation knows Persian); `current` lists the viewed category's children, or its sisters when it has none. The picture and post looks query per category, so they stop at 12. Each item carries `--stx-cat` (Studiare's colour) and the CSS derives `--stx-cat-ink`/`--stx-cat-soft` from it, so text never sits on the raw colour. The drop-down is a native `<details>` (blog.js closes it on an outside tap, Esc or focus leaving); blog.js also scrolls the current button/tab into view. The overlay shade is a `::after`, because Elementor's lazy-background rule strips `background-image` from elements (not pseudo-elements). The phone swipe row copies `.stx-swipe` from home.css, which blog pages do not load.

## Product cards and newer home widgets

- **Card styles.** `Cards::product()`: shop, compact, course (first teacher from `Parts::teacher_ids()`, facts lessons/duration/students; Studiare's lessons meta key is `_studiare_course_lesseons`), overlay (the text band lets clicks through to the picture link), minimal (cart button on the picture, shown on hover with a mouse and always on touch), classic (centred, stars, full-width buy button), list (rows; numbers through `.stx-products__grid--numbers`). A new style needs rules in the "Product grid" section of home.css and its controls' conditions in Product_Grid.
- **Product spotlight** reuses `Countdown::markup()` (home.js ticks it) and `Cards::cart_link()`; its countdown uses the product's sale end date, and the sold/left bar only shows for stock-managed products.
- **Logo strip** marquee is CSS only: the list is printed twice (the copy `aria-hidden` and `inert`), both slide by their width plus the gap, and it stands still and wraps with reduced motion.

## About & contact: key contracts

- **Contact form.** The handler reads the widget's options (fields, topics, recipient, email on/off) from the saved Elementor data, found by the document ID and element ID in the form, never from the request, so a crafted post cannot pick the recipient or the fields. Public cached form: no nonce; honeypot `stx_website`, 3 messages per visitor per 10 minutes, counted only after validation. When phone and email are both optional, one of them is required. Messages are private `stx_message` posts; `Contact_Inbox` shows them in WordPress's list table (the edit screen redirects there). Error texts live in `Contact_Messages::error_messages()` for both the no-JS status and pages.js.
- **select2.** Studiare turns every `<select>` into select2 (`select_to_select2` in global.js). pages.css matches the select2 box to our fields; after a successful send pages.js fires `change` on the selects so select2 shows the reset value, and it focuses the select2 box of an invalid select.
- **Map.** OpenStreetMap (a bbox around the pin) or Google (`maps?q=…&output=embed`), both without an API key. Pasted embeds are accepted only over https from google.com, neshan.org, balad.ir, openstreetmap.org and map.ir. Route links: Neshan `neshan.org/maps/routing/car/destination/LAT,LNG` and Balad `balad.ir/location?latitude=…&longitude=…` (taken from their web apps' routes), Google `maps/dir/?api=1&destination=`, Waze `ul?ll=…&navigate=yes`. The frame has a fixed height before loading; "load on click" is a link to the map without JS.
- **Contact details.** The "support" source uses `Support_Button\Channels::ready()`, the same list the floating button shows. Numbers and usernames are wrapped in `<bdi dir="ltr">`: they read left to right but still line up with the page. Never call them "کانال" in Persian.
- **Key numbers count-up** (home.js): parses the digits (Latin, Persian or Arabic) and separators of the final text, pins its width, hides the moving copy from screen readers, and does nothing with reduced motion, in the editor or without IntersectionObserver. Tiles and large numbers are `column-reverse` items, so they align with `justify-content: flex-end`.
- **is_dynamic_content()** must not read widget settings: Elementor also calls it on the bare widget type while building the editor config, and a settings read there breaks the editor for every page.
- **Centred headings.** Heading's text label and its narrower xl subtitle follow `--stx-justify` (set by the alignment control), not only `text-align`.

## Studiare integration points (verified against theme 13.4)

| Need | Theme hook / selector |
| --- | --- |
| Options | Redux option row `codebean_option` (`primary_color`, `secondary_color`, `font_body`, `menu_heading`, `sc_darkmode_ready`, `off_canvas_cart`, …) |
| Colour variables | `--primary_color`, `--secondary_color`, `--font_body-color`, dark: `--dark_primary_color` (surfaces), `--dark_secondary_color` (header/bg), `--dark_light_color` (text) |
| Fonts | `--font_body-font-family`, `--menu_heading-font-family`, fallback `--fallback-font` |
| Dark mode | class `scdarkcolors` on `<html>` and `<body>`; `localStorage.darkMode = enabled/disabled`; toggles `.dark-mode-toggle` |
| Native bottom bar | `sc_adding_btm_menu_for_mobile` on `wp_footer`; assets `mobile-btm-menu.css/js` (handles live in encoded files, so we dequeue by src) |
| Mini cart | `.sc-cart-offcanvas` + class `active` (theme closes it on outside clicks, so open it after the click finishes) |
| Mobile menu | `body.off-canvas-open`, `.off-canvas-navigation` |
| Login popup | `.register-modal-opener` |
| Cart count fragment | theme: `span.studiare-cart-number`; ours: `span.stx-bn-cart-count` |
| Fixed elements to lift | `.sc_studi_btm_addtocart_fixed_btn_holder_container.sc_add_to_cart_fixed_active`, `.studi_custom_floating_btn`, `a.swss_floting_ticket`, `#back-to-top` |
| Page title bar | page meta `_studiare_disable_title` / `_studiare_disable_breadcrumbs` (`on`), read in `inc/templates/page-title.php` |
| Teachers | `teacher` post type (from the Studiare Core plugin), job title in `_studiare_teacher_job_title` |
| Category icon | term meta (attachment ID): `sc_studi_blog_cat_icon` for blog categories, `sc_studi_cat_icon` for product categories (`Theme_Bridge::category_icon_id()`) |
| Blog category colour | term meta `sc_studi_blog_cat_color` ("Featured Color", hex; `Theme_Bridge::category_color()`) |
| Select boxes | every `<select>` becomes select2 on load (`select_to_select2` in `assets/js/global.js`, 13.3+) |

The theme's `.widget_shopping_cart_content` rules (full-height flex) are undone inside `.stx-sheet--cart`. Studiare's mini-cart template uses `.cart-item-image` and `.cart-item-content`.

## Workflows

- **Lint PHP:** `vendor/bin/phpcs --standard=phpcs.xml.dist`. Install once with Composer: `wp-coding-standards/wpcs:^3.1` and `phpcompatibility/phpcompatibility-wp`. Must report 0/0. `phpcbf` fixes formatting.
- **Syntax check:** `php -l` on every PHP file and `node --check` on every JS file.
- **Minify:** after editing front-end CSS/JS run `node tools/minify.mjs` (esbuild via npx). The plugin serves the `.min` copies unless SCRIPT_DEBUG; build-zip.sh runs it too. Edit the sources, never the `.min` files.
- **Icons:** edit `tools/icon-map.mjs` (semantic key → name per pack, Persian label, keywords, Font Awesome class), then run `node tools/build-icons.mjs`. Packs are downloaded from jsDelivr at build time and committed. The plugin never loads icons from a CDN.
- **Translations (WP-CLI):**
  1. `wp i18n make-pot studiare-extensions studiare-extensions/languages/studiare-extensions.pot --domain=studiare-extensions --exclude=assets`
  2. `wp i18n update-po studiare-extensions/languages/studiare-extensions.pot studiare-extensions/languages/`
  3. Translate the new entries in `studiare-extensions-fa_IR.po`. Use Persian punctuation («») and ZWNJ (نیم‌فاصله).
  4. `wp i18n make-mo studiare-extensions/languages/studiare-extensions-fa_IR.po studiare-extensions/languages/`
- **Package:** `tools/build-zip.sh` creates `dist/studiare-extensions-<version>.zip`.
- **Release:** bump `Version` and `STUDIARE_EXT_VERSION` in `studiare-extensions.php`, update `Stable tag` and the changelog in `readme.txt`.

## Testing (what "done" means)

Local test site: WordPress on SQLite (`sqlite-database-integration` drop-in), WooCommerce, Redux Framework, Elementor and a test copy of Studiare. Since September 2026 the real theme no longer runs locally: ionCube 15 refuses Studiare's encoded files, and the RTL-CareUnit license plugin the theme installs needs ionCube 15. The test copy is the theme's plain files (357 of 363) with small stand-ins for the 6 encoded ones (`sc_main_functions.php`, `inc/codebean_functions.php`, `inc/sc_shortcodes.php`, the `cdb_blog_posts` widget files and the license file). It is never shipped. Its header, footer, CSS and blog templates are the real ones, but its own dark mode switch and a few features in the encoded files are missing, so dark mode is tested by adding `scdarkcolors` to `<html>` and `<body>`. The stand-ins must also define the functions the theme's plain files call (`studiare_needs_header()`, `studiare_page_title()`, `studiare_breadcrumbs()`, `codebean_get_config()` which returns `inc/codebean_<name>.php`, …) and include the plain files the encoded loader normally loads (`public_functions.php`, `inc/mega-menus.php`, `inc/mobile_btm_menu.php`, `inc/lib/suncode_course_lessons.php`, `inc/sc_tools/horizontal_menu_walker/horizontal_menu_walker.php`). No ionCube loader is needed: serve it with `php -d memory_limit=1024M -S 127.0.0.1:8888 -t wp`, with `WP_HTTP_BLOCK_EXTERNAL` on (outside requests hang the single-threaded server). Playwright's browser CDN is blocked from Iran, so point `chromium.launch()` at an already cached browser with `executablePath`, and let the page skip non-local requests. Check with Playwright/Chromium at 390×844 and 1440×900:

- all 5 styles with 2, 4, 5 and 7 items, in light and dark mode, RTL and LTR
- current-page detection (home, shop, search, account, cart) with pretty and plain permalinks
- search sheet (focus, Esc, Back button, submit leaves no extra history entry), live results (debounce, empty/no-result states, arrow keys, tapping a result leaves no extra history entry, hidden products excluded), cart sheet and the Studiare mini cart, theme menu, content sheet with drag to close, back to top, dark-mode toggle persistence
- live cart badge through WooCommerce AJAX fragments (`?wc-ajax=add_to_cart`), with the bump animation
- admin: every tab, style cards, add/duplicate/delete/reorder buttons, icon picker, media picker, colour reset, Ctrl/⌘+S save, reload persistence, dashboard switch
- blog: each archive design on the posts page, a category, a tag, an author, page 2 and a blog search; each post design with a table (scrolls on phones), code, a long title, no picture, and comments; table of contents jumps and highlights; copy link; dark mode
- about/contact: each design on a phone and desktop, in dark mode; key numbers count up; contact form with JS and as a plain post (invalid fields marked, success reset incl. select2, rate limit, honeypot, forged form ID, email copy via `pre_wp_mail`); map click-to-load and route links; the Contact messages list; each design opens in the Elementor editor
- theme fixes: the OTP forms need Studiare Core, which the test site lacks; test with a scratch mu-plugin that enqueues the vendor's two OTP scripts (from a live Studiare site) under their handles, prints the combined form markup and answers the AJAX actions by echoing what they received. Type ۰۹۱۲…, paste "+۹۸ ۹۱۲ …", type 912… and press Enter, set the value without keystrokes and tap send, and check the older login form; with the fix off the theme must show «شماره تلفن باید دقیقاً 11 رقم داشته باشد».
- no console errors, and nothing new in `wp-content/debug.log`
