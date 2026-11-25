// Membuat salinan statis situs lokal untuk demo di GitHub Pages.
// Pakai: node deploy/snapshot.mjs <nama-repo> [base]   contoh: node deploy/snapshot.mjs mis-demo
import { mkdir, writeFile, rm, readdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';

const REPO = process.argv[2] || 'mis-demo';
const BASE = (process.argv[3] || 'http://127.0.0.1:8010').replace(/\/$/, '');
const PREFIX = '/' + REPO;
const OUT = join(process.cwd(), 'deploy', '_site');
const MAX = 1500;
// Halaman per pertemuan kajian dibatasi kira-kira 2 bulan ke belakang dan 6 pekan ke depan.
const day = (d) => d.toISOString().slice(0, 10).replace(/-/g, '');
const FROM = day(new Date(Date.now() - 60 * 864e5)), TO = day(new Date(Date.now() + 42 * 864e5));
const inWindow = (p) => { const m = p.match(/^\/kajian\/[^/]+\/(\d{8})$/); return !m || (m[1] >= FROM && m[1] <= TO); };

const seen = new Set(); const queue = ['/', '/sitemap.xml'];
const BANNER = '<div style="background:#1a0a08;color:#fff7ec;font:600 13px/1.4 system-ui,sans-serif;text-align:center;padding:8px 16px">Versi demo untuk pratinjau, bukan situs resmi. Data dan rekening hanya contoh tampilan.</div>';

const pathOf = (u) => { try { const x = new URL(u, BASE + '/'); if (x.origin !== new URL(BASE).origin) return null; return decodeURIComponent(x.pathname); } catch { return null; } };
const add = (u) => { const p = pathOf(u); if (p && !seen.has(p) && !p.startsWith('/admin') && !p.startsWith('/livewire') && !p.startsWith('/pasang') && inWindow(p)) queue.push(p); };
const fileFor = (p, html) => html ? join(OUT, p, 'index.html') : join(OUT, p);
const rewrite = (s) => s
  .replace(/((?:href|src|action|content|poster|data-[a-z-]+)=["'])\/(?!\/)/g, `$1${PREFIX}/`)
  .replace(/url\((["']?)\/(?!\/)/g, `url($1${PREFIX}/`)
  .split(BASE).join(PREFIX);

await mkdir(OUT, { recursive: true });
for (const e of await readdir(OUT)) if (e !== '.git') await rm(join(OUT, e), { recursive: true, force: true }); // .git cabang gh-pages dipertahankan
let n = 0;
while (queue.length && n < MAX) {
  const p = queue.shift(); if (seen.has(p)) continue; seen.add(p);
  const r = await fetch(BASE + encodeURI(p), { redirect: 'manual' });
  if (r.status !== 200) { console.log(r.status, p); continue; }
  const type = r.headers.get('content-type') || ''; n++;
  if (type.includes('text/html')) {
    let h = await r.text();
    for (const m of h.matchAll(/(?:href|src|poster)=["']([^"']+)["']/g)) add(m[1]);
    for (const m of h.matchAll(/srcset=["']([^"']+)["']/g)) m[1].split(',').forEach(s => add(s.trim().split(/\s+/)[0]));
    h = rewrite(h).replace('<head>', '<head>\n<meta name="robots" content="noindex,nofollow">').replace(/(<body[^>]*>)/, `$1${BANNER}`);
    const f = fileFor(p, true); await mkdir(dirname(f), { recursive: true }); await writeFile(f, h);
  } else if (/css|javascript|json|xml|manifest/.test(type)) {
    let t = await r.text();
    if (type.includes('css')) for (const m of t.matchAll(/url\(["']?([^"')]+)["']?\)/g)) if (!m[1].startsWith('data:')) add(new URL(m[1], BASE + p).href);
    if (type.includes('xml')) for (const m of t.matchAll(/<loc>([^<]+)<\/loc>/g)) add(m[1]);
    const f = fileFor(p, false); await mkdir(dirname(f), { recursive: true }); await writeFile(f, rewrite(t));
  } else {
    const f = fileFor(p, false); await mkdir(dirname(f), { recursive: true }); await writeFile(f, Buffer.from(await r.arrayBuffer()));
  }
}
const nf = await fetch(BASE + '/halaman-demo-tidak-ada');
await writeFile(join(OUT, '404.html'), rewrite(await nf.text()).replace(/(<body[^>]*>)/, `$1${BANNER}`));
await writeFile(join(OUT, '.nojekyll'), '');
console.log(`Selesai: ${n} berkas di ${OUT}, sisa antrean ${queue.length}`);
