const CACHE_NAME = 'farmacia-sjb-v1';
const OFFLINE_URL = '/offline.html';
const INITIAL_ASSETS = [OFFLINE_URL, '/manifest.webmanifest', '/css/bootstrap.css', '/css/custom.css', '/js/bootstrap.bundle.min.js', '/js/app.js', '/js/chart.umd.min.js'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(INITIAL_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))));
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request).then((response) => response || caches.match(OFFLINE_URL))));
});
