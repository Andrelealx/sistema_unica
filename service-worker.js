const CACHE_NAME = 'chamadosunica-cache-v1';
const urlsToCache = [
  '/',
  '/admin/painel.php',
  '/admin/dashboard.php',
  '/admin/visitas.php',
  '/admin/metas.php',
  '/assets/css/admin-estilos.css',
  '/assets/img/logo.png',
  '/assets/img/caju.png',
  // Adicione outros arquivos essenciais para o funcionamento offline
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
          .then(response => {
            // Se estiver no cache, retorna-o; senão, busca na rede
            return response || fetch(event.request);
          })
  );
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(keyList =>
      Promise.all(keyList.map(key => {
        if (!cacheWhitelist.includes(key)) {
          return caches.delete(key);
        }
      }))
    )
  );
});
