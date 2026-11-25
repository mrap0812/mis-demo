// Pratinjau hasil snapshot seperti di GitHub Pages: http://127.0.0.1:8020/mis-demo/
import { createServer } from 'node:http'; import { readFile, stat } from 'node:fs/promises'; import { join, extname } from 'node:path';
const ROOT = join(process.cwd(), 'deploy', '_site'), P = '/' + (process.argv[2] || 'mis-demo');
const T = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.webp': 'image/webp', '.jpg': 'image/jpeg', '.png': 'image/png', '.svg': 'image/svg+xml', '.woff2': 'font/woff2', '.xml': 'application/xml', '.webmanifest': 'application/manifest+json' };
createServer(async (q, r) => {
  let u = decodeURIComponent(new URL(q.url, 'http://x').pathname);
  if (!u.startsWith(P)) { r.writeHead(404); return r.end(); }
  let f = join(ROOT, u.slice(P.length));
  try { if ((await stat(f)).isDirectory()) f = join(f, 'index.html'); r.writeHead(200, { 'content-type': T[extname(f)] || 'application/octet-stream' }); r.end(await readFile(f)); }
  catch { r.writeHead(404, { 'content-type': 'text/html' }); r.end(await readFile(join(ROOT, '404.html'))); }
}).listen(8020, '127.0.0.1');
