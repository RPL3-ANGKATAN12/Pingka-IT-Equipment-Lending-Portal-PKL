const CACHE_NAME = 'portal-peminjaman-v1';

self.addEventListener('install', event => {
    self.skipWaiting();}
);
self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});
