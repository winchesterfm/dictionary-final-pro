// sw.js

const CACHE_NAME = 'dict-cache-v1';
const URLS_TO_CACHE = [
  '/',               // votre page d'accueil
  '/index.php',
  '/search.php',
  '/assets/css/bootstrap.min.css',
  '/assets/css/dark.css',
  '/assets/js/bootstrap.bundle.min.js',
  '/assets/js/jquery.min.js',
  // ajoutez ici tous les fichiers statiques dont vous avez besoin offline
];

self.addEventListener('install', event => {
  // Lors de l'installation, on met en cache les URLs listées
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(URLS_TO_CACHE))
  );
});

self.addEventListener('fetch', event => {
  // Pour chaque requête, on répond d'abord par le cache, sinon on va chercher sur le réseau
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        return response || fetch(event.request);
      })
  );
});

self.addEventListener('activate', event => {
  // Nettoyage des anciens caches si besoin
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(key => key !== CACHE_NAME)
          .map(oldKey => caches.delete(oldKey))
      )
    )
  );
});
