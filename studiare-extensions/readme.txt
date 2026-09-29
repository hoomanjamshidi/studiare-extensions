=== Studiare Extensions ===
Tags: studiare, bottom navigation, elementor, woocommerce, rtl
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Extra features for the Studiare LMS theme: a customizable mobile bottom navigation, Elementor page templates for courses, products, headers, footers and the blog, ready-made home, about us and contact us pages, and a floating support button.

== Description ==

= Mobile bottom navigation =

* Five styles: Classic, Floating glass, Center button (notch), Bubble and Expanding pill — each with a live preview.
* Any number of buttons (up to 8); the layout adapts so it always looks balanced.
* Button types: home, custom link, search sheet, cart (live WooCommerce count, opens the Studiare mini cart), account (avatar and guest label), menu, content sheet (Elementor/shortcode), back to top, dark mode, and "click an element".
* Six bundled SVG icon packs (Lucide, Tabler, Phosphor, Phosphor Duotone, Heroicons, Bootstrap Icons) plus Studiare's Font Awesome, custom images or custom SVG per button.
* Colours, fonts and dark mode follow the Studiare theme by default; every colour, size and font can be overridden.
* Cache friendly (no server-side device detection), accessible (keyboard, screen readers, reduced motion) and RTL-first.

= Page templates (Elementor) =

* 36 ready-made designs, installed as normal Elementor templates: 3 course pages, 3 product pages, 7 headers, 8 footers, 3 home pages, 3 about us pages, 3 contact us pages, 3 blog post lists and 3 blog posts.
* Pages: pick a home, about us or contact us design and create a real WordPress page from it in one click (a home design can become the site's home page), then edit it like any Elementor page. Products, categories, posts and Studiare teachers fill in from the site.
* Blog: one design for post lists (the blog page, categories, tags, authors, dates and blog searches, with page numbers) and one for single posts, with a table of contents, share buttons, author box, related posts and Studiare's own comments.
* Use Studiare's own layout or a template — for all courses/products, per product category (with or without subcategories), or per product from its edit screen.
* Separate header and footer for desktop and for phones/tablets (template, the theme's own, or nothing). Both are printed and switched with CSS, so page caching keeps working.
* 68 Elementor widgets in a "Studiare+" category: product title, price, add to cart (variations, quantity, "already enrolled", "in cart"), gallery with the course intro video, course facts, curriculum from Studiare's lessons, teacher, tabs, reviews, related products, mobile buy bar, logo, menu with mobile drawer, search, cart, login, dark mode switch, copyright (Solar Hijri year), e-Namad badges; for home pages: a fast slider in six designs, hero slides, promo card, product grid (seven card designs, category buttons), product spotlight with a sale countdown, category grid, post grid (blog, magazine, podcasts), features, countdown, offer price, testimonials, teachers & team, events, FAQ (with FAQ structured data), key numbers, picture/video (YouTube, Aparat, Vimeo), logo strip, pricing plans and a newsletter form with a built-in list (CSV download, WordPress privacy tools); for about and contact pages: contact form, map, contact details and timeline; and for the blog: page title, breadcrumb, categories, post title, post details, featured image, post content, table of contents, share buttons, tags, author box, previous/next post, comments and a reading progress bar.
* Persian-first: RTL layouts, Persian digits and separators, widgets inherit the site's Persian font instead of Elementor's default fonts.
* Brand colours in one place (with Studiare dark mode); container "brand surface" and "sticky" options in Elementor.
* Live preview of every design on desktop, tablet and phone before saving.
* Needs Elementor (free) 3.16 or newer with Flexbox Containers; tested with Elementor 3.35 and 4.3 and WooCommerce 11.

= Floating support button =

* One corner button for Telegram, WhatsApp, Bale, Eitaa, Instagram, phone, email and any support page. Type a username, a number (Persian digits and 09… mobiles work) or a link; the admin shows the exact link each channel opens.
* Two designs: a card with a heading and one row per channel, or round brand-coloured bubbles. A single channel turns the button into a direct link with that app's logo.
* WhatsApp can open with a ready-made first message; an optional greeting appears once per visit after a delay you choose.
* Rises automatically above the Studiare+ bottom navigation, sticky buy bars and the theme's "back to top" button, and follows Studiare's colours and dark mode.
* Works without JavaScript (a native disclosure), is cache friendly, and is keyboard and screen reader accessible.

== Installation ==

1. Upload the `studiare-extensions` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open **Studiare+ → Mobile bottom navigation**, **Studiare+ → Page templates** or **Studiare+ → Floating support button** in the admin menu.

== Changelog ==

= 1.6.0 =
* New: Page templates → Blog. Three designs for post lists (Magazine, With sidebar, Minimal) and three for single posts (Classic, Focus, Cover). Post lists cover the blog page, categories, tags, authors and dates (and, when switched on, blog searches), with page numbers.
* New: 14 blog widgets: blog page title, blog breadcrumb, blog categories, post title, post details (author, date, last update, reading time, comments), featured image (loads first for PageSpeed), post content styled for reading (headings, quotes, code, tables that scroll on phones), table of contents (marks the section being read), share buttons (Telegram, WhatsApp, X, LinkedIn, email, copy link and the phone's share sheet), tags, author box, previous/next post, comments (Studiare's own comment form) and a reading progress bar.
* New: Post grid lists "Posts of the page being viewed" with page numbers and "Related to the post being viewed", has a Magazine layout and "Highlight the first post", and three new cards: Editorial, Row and Compact list (with numbers).
* New: five more product card designs in Product grid: Course (teacher, lessons, duration, students), Picture, Minimal, Classic shop and Row (with numbers, for "Top 5" lists).
* New home widgets: Product spotlight (a deal of the day with a countdown to the end of its sale and a sold/left bar), Logo strip (a moving row or a grid) and Pricing plans.
* New: About us and contact us pages. Page templates → Pages (formerly "Home pages") has three about designs (Story, Minimal, Academy) and three contact designs (Cards, Split, Support centre). "Create page" makes a normal Elementor page from any of them.
* New: Contact form widget. Messages are kept in Studiare+ → Contact messages (a list with a "new" count, reply by email, CSV download, WordPress privacy tools) and can also be emailed to an address set on the widget. Works without JavaScript and on cached pages (honeypot + rate limit, no nonce). A form plugin's shortcode can replace it.
* New: Map widget: OpenStreetMap or Google Maps with no API key, or a pasted Neshan/Balad embed. Optional "load on click", an address card, and directions buttons for Neshan, Balad, Google Maps and Waze.
* New: Contact details widget (phone, email, address, hours, Telegram, WhatsApp, Bale, Eitaa, Instagram) as cards, a list or brand buttons. It can show the floating support button's channels, so they are typed once.
* New: Timeline widget (line, both sides, or steps in a row).
* Improved: Key numbers widget: icons, "Tiles" and "Large numbers" looks, columns, and a count-up that keeps the layout still and leaves the real number for search engines and screen readers.
* Improved: X and LinkedIn icons in every icon pack.
* Fix: a centred Heading now also centres its small label and its (narrower) subtitle.

= 1.5.0 =
* New: Floating support button (Studiare+ → Floating support button). Telegram, WhatsApp, Bale and Eitaa, plus Instagram, phone, email and a custom support page; drag to reorder. Card or bubbles design, brand colours, WhatsApp first message, optional greeting, left or right corner, per-device display.
* The button moves up by itself above the bottom navigation (and back down when the navigation hides while scrolling), above sticky buy bars and above the theme's "back to top" button.

= 1.4.0 =
* New: Slider widget in six designs: banner, hero with text, centre with side peeks, cards (several at once), with side banners, and with title tabs. Separate phone pictures, slide or fade effect, autoplay with a pause button, arrows, dots and keyboard/swipe control.
* New: Slider picture loading for PageSpeed and GTmetrix: "Top of the page" loads the first picture at once with high priority (and tells caching plugins not to lazy-load it) and each next picture only when it is needed; "Lower on the page" lazy-loads them all; or no lazy loading. The slider keeps its exact space before pictures load (no layout shift), needs no jQuery or carousel library, prints its few kilobytes of CSS inline and loads one small deferred script.
* Improved: the plugin serves minified CSS and JavaScript (readable files with SCRIPT_DEBUG); the brand colours are printed inline, so a page with only the slider does not load the page templates stylesheet.

= 1.3.1 =
* New: Product grid → "Slide on every screen": the cards sit in one row that slides sideways on computers too, with arrow buttons and mouse dragging; "Columns" sets how many are in view. The shop grid of the home page designs uses it and shows 8 products.

= 1.3.0 =
* New: Page templates → Home pages. Three ready-made home page designs (Complete, Studiare, Bright shop); "Create page" makes a normal Elementor page from a design, published or as a draft, and can make it the site's home page. Studiare's title bar is switched off on these pages.
* New: 15 widgets for home pages (see the description). Grids can become a swipe row on phones; picture placeholders keep a page looking finished until real pictures are added.
* New: newsletter list for the Newsletter widget: no setup needed, spam-protected without breaking cached pages, downloadable as CSV, and included in WordPress's personal data export and erase tools. Any newsletter plugin's form can be used instead (shortcode).
* New: Heading widget: section-title style (« title ——— link), a link at the end of the row, pill labels and <mark> highlights. Icon list: numbered steps. Button: "White" style. Containers: "Accent gradient" surface.
* Fix: in Studiare's dark mode, text on light and amber buttons turned white (the theme colours every link white); buttons now keep their own colours. Highlighted text and sale prices no longer get the theme's dark highlight box.

= 1.2.1 =
* Fix: live search no longer lists the home page (or the blog, shop, cart, checkout, account and Studiare header/footer pages) for almost every term. Elementor stores a text copy of everything these pages show, so they matched any search.
* Fix: with the admin bar on phones, the sticky header stopped 46px below the top and page content showed above it.
* Fix: a smart sticky header no longer covers the sticky buy box or the section jump bar when it slides back in.
* Change: the sticky header setting is now the first card of Page templates → Header, named "Sticky header" (Off / Always / Smart).
* Change: the floating header fades the page out behind its bar while stuck.

= 1.2.0 =
* Fix: course pages showed "You are enrolled" to every logged-in user (Studiare's purchase check returns the text "false", which counted as yes). Enrollment now uses the same test as Studiare's lesson list, subscriptions included.
* Fix: the header cart button did not open Studiare's mini cart (the theme closed it again on the same click).
* Fix: on phones the course cover collapsed and covered the title and excerpt.
* Fix: Studiare's product-page script no longer throws on every scroll when the footer is replaced.
* New: every header design has its own compact phone bar (menu, logo, search, cart); logos are sized by height so square logos stay small. Two new headers: Dark and Minimal.
* New: live search results under the header search field and in the search overlay (can be switched off per widget).
* New: three new footers (Split, Support, Soft); every footer centres its content on phones.
* New: Course · Spotlight keeps the buy card beside the title from the top of the page.
* New: bottom navigation can be hidden on single course or product pages.
* New: live search results in the bottom navigation's search sheet (can be switched off per button), and the button can search several post types at once.
* New: the search sheet's results show course covers, highlighted matches, prices (sale prices included) or dates, a result count, a "See all results" button, and loading placeholders.
* Change: the course curriculum shows Studiare's own lesson list to everyone by default.
* Change: templates use Elementor's site container width, so headers and footers line up with the page content; unedited ready-made templates are updated automatically.
* Change: darker muted and accent text colours for readable small text.

= 1.1.0 =
* New: Page templates — Elementor designs for course pages, product pages, headers and footers, with category rules, per-device headers/footers and live previews.

= 1.0.0 =
* First release: mobile bottom navigation.

== Credits ==

* Vazirmatn font — SIL Open Font License 1.1 (assets/admin/fonts/OFL.txt).
* Icons: Lucide (ISC), Tabler Icons (MIT), Phosphor Icons (MIT), Heroicons (MIT), Bootstrap Icons (MIT).
