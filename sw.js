// Service worker for DukaanDesk.
// It only keeps the static files (css, js, icons) and an offline page.
// Pages with shop data are never stored, they always come from the server.
var CACHE = 'dukaandesk-v1';
var FILES = [
    'offline.html',
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/js/billing.js',
    'assets/icons/icon-192.png'
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) { return cache.addAll(FILES); })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (names) {
            return Promise.all(names.filter(function (n) { return n !== CACHE; })
                                    .map(function (n) { return caches.delete(n); }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;

    // opening a page: go to the server, show the offline page if there is no internet
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(function () { return caches.match('offline.html'); })
        );
        return;
    }

    // our own css / js / icons: try the network first so updates show up, use the saved copy when offline
    var url = new URL(req.url);
    if (url.origin === self.location.origin && url.pathname.indexOf('/assets/') !== -1) {
        event.respondWith(
            fetch(req).then(function (res) {
                var copy = res.clone();
                caches.open(CACHE).then(function (cache) { cache.put(req, copy); });
                return res;
            }).catch(function () { return caches.match(req); })
        );
    }
});
