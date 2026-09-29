#!/usr/bin/env node
/**
 * Builds the bundled SVG icon packs from their npm packages (via jsDelivr).
 *
 * Output (committed to the plugin, so the plugin never loads icons from a CDN):
 *   studiare-extensions/assets/icons/catalog.json      — semantic keys, labels, pack meta
 *   studiare-extensions/assets/icons/packs/<pack>.json — { key: { svg, active? } }
 *
 * Usage: node tools/build-icons.mjs
 */

import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { ICONS, PACKS } from './icon-map.mjs';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const OUT_DIR = join(ROOT, 'studiare-extensions', 'assets', 'icons');
const CDN = 'https://cdn.jsdelivr.net/npm';

/**
 * Fetches a file from the CDN; resolves to null on 404 so optional
 * variants (filled icons) can be probed without special casing.
 */
async function fetchText(url) {
  const res = await fetch(url);
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`HTTP ${res.status} for ${url}`);
  return res.text();
}

/**
 * Normalises an SVG for inline use: strips comments, XML prologs, sizing,
 * classes and invisible helper paths, and collapses whitespace.
 */
function cleanSvg(svg) {
  return svg
    .replace(/<\?xml[^>]*>/g, '')
    .replace(/<!--[\s\S]*?-->/g, '')
    .replace(/\s(width|height|class|xmlns(:\w+)?)="[^"]*"/g, '')
    .replace(/<path stroke="none" d="M0 0h24v24H0z" fill="none"\s*\/>/g, '')
    .replace(/\s+/g, ' ')
    .replace(/\s*(\/?>)/g, '$1')
    .replace(/>\s+</g, '><')
    .trim();
}

async function buildPack(id, pack) {
  const namesKey = pack.namesFrom || id;
  const base = `${CDN}/${pack.npm}@${pack.version}`;
  const icons = {};
  const missing = [];

  await Promise.all(
    ICONS.map(async (icon) => {
      const name = icon.names[namesKey];
      if (!name) return;

      const svg = await fetchText(`${base}/${pack.outline(name)}`);
      if (!svg) {
        missing.push(`${icon.key} (${name})`);
        return;
      }

      const entry = { svg: cleanSvg(svg) };
      if (pack.filled) {
        const filled = await fetchText(`${base}/${pack.filled(name)}`);
        if (filled) entry.active = cleanSvg(filled);
      }
      icons[icon.key] = entry;
    })
  );

  // Keep catalog order stable for readable diffs.
  const ordered = {};
  for (const icon of ICONS) {
    if (icons[icon.key]) ordered[icon.key] = icons[icon.key];
  }

  await writeFile(join(OUT_DIR, 'packs', `${id}.json`), JSON.stringify(ordered));
  const count = Object.keys(ordered).length;
  console.log(`✓ ${id}: ${count}/${ICONS.length} icons` + (missing.length ? ` — missing: ${missing.join(', ')}` : ''));
}

async function main() {
  await mkdir(join(OUT_DIR, 'packs'), { recursive: true });

  for (const [id, pack] of Object.entries(PACKS)) {
    await buildPack(id, pack);
  }

  const catalog = {
    icons: ICONS.map(({ key, label, keywords, fa }) => ({ key, label, keywords, fa })),
    packs: Object.fromEntries(
      Object.entries(PACKS).map(([id, p]) => [
        id,
        { label: p.label, license: p.license, source: `${p.npm}@${p.version}`, hasFilled: Boolean(p.filled), fallback: p.fallback },
      ])
    ),
  };
  await writeFile(join(OUT_DIR, 'catalog.json'), JSON.stringify(catalog, null, 1));
  console.log('✓ catalog.json');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
