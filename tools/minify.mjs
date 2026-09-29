#!/usr/bin/env node
/**
 * Writes minified copies (`*.min.css`, `*.min.js`) of the front-end assets
 * next to their sources. The plugin serves them unless SCRIPT_DEBUG is on
 * (Core\Asset::url()), so PageSpeed and GTmetrix see no "Minify CSS/JS"
 * items even on hosts without a cache plugin. The sources stay readable and
 * are the files to edit; build-zip.sh runs this before packaging.
 *
 * Uses esbuild through npx (downloaded on first run, never shipped).
 * Target `esnext`, so modern syntax (:is(), logical properties, color-mix)
 * is only compressed, never rewritten.
 *
 * Usage: node tools/minify.mjs
 */

import { execFileSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', 'studiare-extensions', 'assets');

// Only what the front end loads; admin files are not measured by PageSpeed.
const FILES = [
  'modules/builder/css/builder.css',
  'modules/builder/css/home.css',
  'modules/builder/css/blog.css',
  'modules/builder/css/pages.css',
  'modules/builder/css/slider.css',
  'modules/builder/js/builder.js',
  'modules/builder/js/home.js',
  'modules/builder/js/blog.js',
  'modules/builder/js/pages.js',
  'modules/builder/js/slider.js',
  'modules/bottom-nav/css/bottom-nav.css',
  'modules/bottom-nav/js/bottom-nav.js',
  'modules/support-button/css/support-button.css',
  'modules/support-button/js/support-button.js',
  'modules/theme-fixes/js/otp-digits.js',
];

for (const file of FILES) {
  const source = join(ROOT, file);
  const target = source.replace(/\.(css|js)$/, '.min.$1');
  execFileSync('npx', ['--yes', 'esbuild@0.25', source, '--minify', '--target=esnext', '--legal-comments=none', `--outfile=${target}`, '--log-level=warning'], { stdio: 'inherit' });
  console.log(file.replace(/\.(css|js)$/, '.min.$1'));
}
