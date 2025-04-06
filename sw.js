const CACHE_NAME = 'grievease-v1';
const CACHE_FILES = [
  '/',
  '/index.php',
  '/manifest.json',
  '/Landing_Page/Landing_images/logo.png'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(CACHE_FILES))
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request)
      .then(response => response || fetch(e.request))
  );
});