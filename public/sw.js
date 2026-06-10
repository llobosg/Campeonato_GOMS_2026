// sw.js - Service Worker Básico para PWA
// sw.js
const CACHE_NAME = 'goms-2026-v3'; // DEBE SER v3 O SUPERIOR

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(name => {
          if (name !== CACHE_NAME) {
            console.log('🗑️ Borrando caché obsoleta:', name);
            return caches.delete(name);
          }
        })
      );
    })
  );
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
const urlsToCache = [
  '/',
  '/assets/css/main.css',
  '/assets/js/app.js',
  '/assets/images/balonfifa2026.png',
  '/assets/images/copa-mundo.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});

// sw.js - Al final del archivo
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting(); // Fuerza la activación inmediata
  }
});

self.addEventListener('activate', event => {
  // Borra cachés viejas automáticamente
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== 'goms-2026-v3') { // Solo mantiene v3
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});