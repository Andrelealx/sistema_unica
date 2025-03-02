const CACHE_NAME = 'unica-chamados-cache-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/assets/css/estilos.css',
  '/assets/img/logo.png',
  // Adicione outros arquivos que deseja cachear
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
