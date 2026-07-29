const CACHE_NAME = 'codemwana-static-v1';
const STATIC_ASSETS = [
  './offline.html',
  './assets/css/app.css',
  './assets/js/app.js',
  './assets/js/playground.js',
  './assets/img/favicon.svg'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.endsWith('.css') || url.pathname.endsWith('.js') || url.pathname.endsWith('.svg')) {
    event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
      const copy = response.clone();
      caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
      return response;
    })));
    return;
  }
  event.respondWith(fetch(event.request).catch(() => caches.match('./offline.html')));
});
