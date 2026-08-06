const CACHE_NAME = 'codemwana-static-v7';
const STATIC_ASSETS = [
  './offline.html',
  './assets/css/app.css',
  './assets/css/app-v3.css',
  './assets/css/app-v4.css',
  './assets/css/curriculum.css',
  './assets/css/remote-runner.css',
  './assets/js/app.js',
  './assets/js/ui-v4.js',
  './assets/js/remote-runner.js',
  './assets/js/playground.js',
  './assets/js/browser-runners.js',
  './assets/js/curriculum.js',
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
  const requestUrl = new URL(event.request.url);
  if (requestUrl.origin !== self.location.origin) return;

  if (requestUrl.pathname.endsWith('.css') || requestUrl.pathname.endsWith('.js') || requestUrl.pathname.endsWith('.svg')) {
    event.respondWith(fetch(event.request).then((response) => {
      if (!response || response.status !== 200) return response;
      const copy = response.clone();
      caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
      return response;
    }).catch(() => caches.match(event.request)));
    return;
  }

  event.respondWith(fetch(event.request).catch(() => caches.match('./offline.html')));
});
