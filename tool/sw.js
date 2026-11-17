self.addEventListener('install', (e) => {
  e.waitUntil(self.skipWaiting());
});
self.addEventListener('activate', (e) => {
  e.waitUntil(self.clients.claim());
});
// Optionnel : intercepte les requêtes pour un mode hors-ligne basique
self.addEventListener('fetch', (e) => {
  // Laisse le navigateur gérer normalement, ou mettez en place un cache
});