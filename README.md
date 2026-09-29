# Studiare Extensions

افزونهٔ وردپرس برای افزودن امکانات به قالب آموزشی Studiare، شامل نوار ناوبری موبایل، قالب‌های صفحهٔ Elementor و دکمهٔ پشتیبانی شناور.

## نیازمندی‌ها

- WordPress 6.0 یا جدیدتر
- PHP 7.4 یا جدیدتر
- قالب Studiare برای یکپارچگی با امکانات قالب
- Elementor 3.16 یا جدیدتر برای قالب‌های صفحه

## نصب

پوشهٔ [`studiare-extensions/`](studiare-extensions/) را در `wp-content/plugins/` کپی کنید و افزونه را از پیشخوان وردپرس فعال کنید. راهنمای امکانات و تغییرات نسخه‌ها در [`studiare-extensions/readme.txt`](studiare-extensions/readme.txt) قرار دارد.

## ساخت بستهٔ نصب

از ریشهٔ پروژه دستور `bash tools/build-zip.sh` را اجرا کنید. بستهٔ خروجی در پوشهٔ `dist/` ساخته می‌شود. این بسته و فایل ZIP قالب Studiare بخشی از کد منبع مخزن نیستند.

## ساختار پروژه

- `studiare-extensions/`: کد و دارایی‌های افزونه
- `tools/`: ابزارهای ساخت دارایی‌ها و بستهٔ نصب
- `CDesign/`: طرح‌های مرجع رابط کاربری
- `phpcs.xml.dist`: تنظیمات بررسی استانداردهای کدنویسی PHP
