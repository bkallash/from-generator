const CACHE_NAME = 'form-pwa-cache-v4';
const OFFLINE_URL = '/offline.html';

// Install event: cache the offline fallback page and skip waiting immediately
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll([OFFLINE_URL]))
            .then(() => self.skipWaiting())
    );
});

// Activate event: clean up old caches and claim all clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            ))
            .then(() => self.clients.claim())
    );
});

// ── Fetch Handler ─────────────────────────────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Never intercept POST / mutation requests — let the browser handle them
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip sync and manifest endpoints — they must always go to network
    if (url.pathname.endsWith('/sync') || url.pathname.endsWith('/manifest.json')) {
        return;
    }

    // ── Public form page: /f/{slug} ──────────────────────────────────────────
    // Use "network first, fall back to cache, fall back to offline page" strategy.
    // Crucially, the cache key is stored WITHOUT query params so that ?page=2 etc.
    // still matches the cached HTML shell when offline.
    const isPublicForm = url.pathname.startsWith('/f/') && !url.pathname.endsWith('/sync');

    if (isPublicForm) {
        event.respondWith(handleFormPageFetch(event.request, url));
        return;
    }

    // ── Build assets: /build/** ───────────────────────────────────────────────
    // Cache-first: assets have content hashes so they never go stale.
    const isBuildAsset =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/@vite/') ||
        url.pathname.startsWith('/resources/');

    if (isBuildAsset) {
        event.respondWith(handleBuildAssetFetch(event.request));
        return;
    }

    // ── Static assets: favicon, icons, fonts ─────────────────────────────────
    const isStaticAsset =
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js');

    if (isStaticAsset) {
        event.respondWith(handleBuildAssetFetch(event.request));
        return;
    }
});

/**
 * Network-first strategy for form pages.
 * Always caches under a "bare" key (no query params) so offline loads work
 * regardless of which page= query param the user last visited.
 */
async function handleFormPageFetch(request, url) {
    // Use the full URL (including ?page=N query params) as the cache key.
    // Each page of a multi-page form is cached as a separate entry so
    // all pages are independently serveable when offline.
    const cacheKeyUrl = request.url;

    const cache = await caches.open(CACHE_NAME);

    try {
        // Try the network first
        const networkResponse = await fetch(request);

        if (networkResponse && networkResponse.ok) {
            // Store the fresh response under the bare pathname key
            cache.put(cacheKeyUrl, networkResponse.clone());
        }

        return networkResponse;
    } catch (_networkError) {
        // Network failed — try to serve from cache
        const cachedResponse = await cache.match(cacheKeyUrl);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Nothing cached yet — show the offline fallback page
        const offlineFallback = await cache.match(OFFLINE_URL);
        return offlineFallback || new Response(
            'You are offline and this form has not been cached yet. Please visit it once while online first.',
            { status: 503, headers: { 'Content-Type': 'text/plain' } }
        );
    }
}

/**
 * Cache-first strategy for versioned build assets.
 */
async function handleBuildAssetFetch(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (_err) {
        // Return an empty 503 for assets — the page may still render partially
        return new Response('', { status: 503 });
    }
}

// ── Background Sync ───────────────────────────────────────────────────────────
self.addEventListener('sync', (event) => {
    if (event.tag === 'form-submission-sync') {
        event.waitUntil(handleSyncEvent(event));
    }
});

async function handleSyncEvent(event) {
    // Check if there are active window clients
    const clientsList = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const hasActiveWindow = clientsList.some(client => client.visibilityState === 'visible' || client.focused);
    
    if (hasActiveWindow) {
        console.log('[SW] Active window client found. Deferring sync to the page.');
        // Notify active window clients to sync
        for (const client of clientsList) {
            client.postMessage({ type: 'TRIGGER_PAGE_SYNC' });
        }
        return;
    }
    
    // If no active window is open, sync in the background
    await syncSubmissions();
}

// ── Message Listener ──────────────────────────────────────────────────────────
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SYNC_NOW') {
        // event.waitUntil is not available on MessageEvent in all browsers;
        // use Promise directly and notify the client of the result.
        syncSubmissions().catch((err) => console.error('[SW] Manual sync failed:', err));
    }

    // Warm-cache request: client sends a list of URLs to pre-cache
    if (event.data && event.data.type === 'CACHE_URLS' && Array.isArray(event.data.urls)) {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) =>
                Promise.allSettled(
                    event.data.urls.map((url) =>
                        cache.add(url).catch((err) =>
                            console.warn('[SW] Failed to pre-cache:', url, err)
                        )
                    )
                )
            )
        );
    }
});

// ── IndexedDB helpers (reused in SW context) ─────────────────────────────────

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('form_offline_queue', 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('submissions')) {
                db.createObjectStore('submissions', { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = (event) => resolve(event.target.result);
        request.onerror = (event) => reject(event.target.error);
    });
}

function getQueuedSubmissions(db) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('submissions', 'readonly');
        const store = tx.objectStore('submissions');
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

function deleteSubmissionFromDb(db, id) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('submissions', 'readwrite');
        const store = tx.objectStore('submissions');
        const req = store.delete(id);
        req.onsuccess = () => resolve();
        req.onerror = () => reject(req.error);
    });
}

function updateSubmissionStatus(db, id, status, retries) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('submissions', 'readwrite');
        const store = tx.objectStore('submissions');
        const getReq = store.get(id);

        getReq.onsuccess = () => {
            const data = getReq.result;
            if (data) {
                data.status = status;
                data.retries = retries;
                const putReq = store.put(data);
                putReq.onsuccess = () => resolve();
                putReq.onerror = () => reject(putReq.error);
            } else {
                resolve();
            }
        };

        getReq.onerror = () => reject(getReq.error);
    });
}

// ── Background sync loop ──────────────────────────────────────────────────────
async function syncSubmissions() {
    let db;
    try {
        db = await openDatabase();
    } catch (err) {
        console.error('[SW] Failed to open IndexedDB:', err);
        return;
    }

    const submissions = await getQueuedSubmissions(db);
    const pending = submissions.filter((s) => s.status !== 'synced');

    if (pending.length === 0) return;

    for (const submission of pending) {
        try {
            await updateSubmissionStatus(db, submission.id, 'syncing', submission.retries || 0);

            const formData = new FormData();
            for (const [key, value] of Object.entries(submission.fields)) {
                if (Array.isArray(value)) {
                    value.forEach(v => formData.append(`fields[${key}][]`, v));
                } else if (value instanceof File) {
                    formData.append(`fields[${key}]`, value);
                } else if (value instanceof Blob) {
                    formData.append(`fields[${key}]`, value, value.name || 'blob');
                } else if (value !== null && value !== undefined) {
                    formData.append(`fields[${key}]`, value);
                }
            }

            const response = await fetch(`/f/${submission.slug}/sync`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (response.ok) {
                await deleteSubmissionFromDb(db, submission.id);

                const clients = await self.clients.matchAll({ includeUncontrolled: true });
                for (const client of clients) {
                    client.postMessage({
                        type: 'SUBMISSION_SYNCED',
                        id: submission.id,
                        number: submission.number,
                    });
                }
            } else if (response.status >= 400 && response.status < 500) {
                // Validation / bad-request — remove it so it doesn't block the queue
                await deleteSubmissionFromDb(db, submission.id);

                const clients = await self.clients.matchAll({ includeUncontrolled: true });
                for (const client of clients) {
                    client.postMessage({
                        type: 'SUBMISSION_FAILED_VALIDATION',
                        id: submission.id,
                        number: submission.number,
                        status: response.status,
                    });
                }
            } else {
                throw new Error(`Server returned ${response.status}`);
            }
        } catch (error) {
            console.error('[SW] Sync error for submission', submission.id, error);
            const retries = (submission.retries || 0) + 1;
            await updateSubmissionStatus(db, submission.id, 'failed', retries);

            const clients = await self.clients.matchAll({ includeUncontrolled: true });
            for (const client of clients) {
                client.postMessage({
                    type: 'SUBMISSION_SYNC_ERROR',
                    id: submission.id,
                    number: submission.number,
                    error: error.message,
                });
            }

            // Re-throw so Background Sync knows to retry later
            throw error;
        }
    }
}
