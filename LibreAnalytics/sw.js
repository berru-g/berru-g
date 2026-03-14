const CACHE_NAME = 'SP_LibreAnalytics';
const urlsToCache = [
  '/smart_pixel_v2/public/login.php',
  '/smart_pixel_v2/public/dashboard.php',
  '/smart_pixel_v2/assets/dashboard.css',
  '/smart_pixel_v2/assets/icons/icon-192x192.png',
  '/smart_pixel_v2/assets/icons/icon-512x512.png',
  'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => response || fetch(event.request))
  );
});
