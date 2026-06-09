// sw.js - Service Worker Básico para PWA
const CACHE_NAME = 'goms-2026-v1';
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