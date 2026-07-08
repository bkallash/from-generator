// PWA Offline Queue and Sync Module

let db;
let currentSlug = '';
let currentForm = null;
let editingId = null;
let installPrompt = null;
let isPageSyncing = false;

// Initialize IndexedDB database for local offline cache
function initDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('form_offline_queue', 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('submissions')) {
                db.createObjectStore('submissions', { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = (event) => {
            db = event.target.result;
            resolve(db);
        };

        request.onerror = (event) => {
            console.error('IndexedDB open error:', event.target.error);
            reject(event.target.error);
        };
    });
}

// Service Worker Registration
export function registerSW() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/f/' })
                .then((registration) => {
                    console.log('[PWA] Service Worker registered, scope:', registration.scope);
                    // Send all page assets to the SW to pre-warm the cache.
                    warmSwCache(registration);
                })
                .catch((error) => {
                    console.error('[PWA] Service Worker registration failed:', error);
                });
        });
    }

    // Capture PWA installation prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        installPrompt = e;
        const installBtn = document.getElementById('pwa-install-btn');
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        if (installBtn && !isStandalone) {
            installBtn.classList.remove('hidden');
        }
    });
}

// Pre-cache primary page and assets so application loads instantly offline
function warmSwCache(registration) {
    const urls = [window.location.origin + '/f/' + window.formSlug];

    document.querySelectorAll('link[rel="stylesheet"]').forEach((el) => {
        if (el.href) urls.push(el.href);
    });
    document.querySelectorAll('script[src]').forEach((el) => {
        if (el.src) urls.push(el.src);
    });
    document.querySelectorAll('link[rel*="icon"]').forEach((el) => {
        if (el.href) urls.push(el.href);
    });
    document.querySelectorAll('link[rel="manifest"]').forEach((el) => {
        if (el.href) urls.push(el.href);
    });

    // Always wait for the SW to be active before sending CACHE_URLS message
    navigator.serviceWorker.ready.then((readyReg) => {
        readyReg.active?.postMessage({ type: 'CACHE_URLS', urls });
    });
}

// ── Offline multi-page draft (localStorage fallback) ───────────────────────
const DRAFT_KEY = 'pwa_form_draft_';

function saveOfflineDraftPage(slug, pageIdx, fields) {
    try {
        const raw = localStorage.getItem(DRAFT_KEY + slug);
        const draft = raw ? JSON.parse(raw) : {};
        draft[pageIdx] = fields;
        localStorage.setItem(DRAFT_KEY + slug, JSON.stringify(draft));
    } catch (e) {
        console.warn('[PWA] Could not save draft page to localStorage', e);
    }
}

function getOfflineDraftPages(slug) {
    try {
        const raw = localStorage.getItem(DRAFT_KEY + slug);
        return raw ? JSON.parse(raw) : {};
    } catch (e) {
        return {};
    }
}

function clearOfflineDraftPages(slug) {
    try {
        localStorage.removeItem(DRAFT_KEY + slug);
    } catch (e) {}
}

// Get the next submission index count number for labeling the boxes
function getNextNumber(slug) {
    return new Promise((resolve) => {
        const transaction = db.transaction('submissions', 'readonly');
        const store = transaction.objectStore('submissions');
        const request = store.getAll();

        request.onsuccess = () => {
            const formSubmissions = request.result.filter(s => s.slug === slug);
            if (formSubmissions.length === 0) {
                resolve(1);
            } else {
                const maxNum = Math.max(...formSubmissions.map(s => s.number || 0));
                resolve(maxNum + 1);
            }
        };
        request.onerror = () => resolve(1);
    });
}

// Save a new submission with pre-merged fields (for offline submissions)
async function saveSubmissionWithFields(slug, fields) {
    const num = await getNextNumber(slug);
    const submission = {
        slug,
        number: num,
        fields,
        savedAt: new Date().toISOString(),
        status: 'queued',
        retries: 0,
    };
    return new Promise((resolve, reject) => {
        const tx = db.transaction('submissions', 'readwrite');
        const store = tx.objectStore('submissions');
        const req = store.add(submission);
        req.onsuccess = () => {
            requestBackgroundSync();
            resolve(req.result);
        };
        req.onerror = (e) => reject(e.target.error);
    });
}

// Update queued submission offline
function updateSubmissionWithFields(id, newFields) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction('submissions', 'readwrite');
        const store = tx.objectStore('submissions');
        const getReq = store.get(id);
        getReq.onsuccess = () => {
            const data = getReq.result;
            if (data) {
                data.fields = { ...data.fields, ...newFields };
                data.savedAt = new Date().toISOString();
                data.status = 'queued';
                const putReq = store.put(data);
                putReq.onsuccess = () => {
                    requestBackgroundSync();
                    resolve();
                };
                putReq.onerror = () => reject(putReq.error);
            } else {
                reject(new Error('Submission not found'));
            }
        };
        getReq.onerror = () => reject(getReq.error);
    });
}

// Delete submission from queue
function deleteSubmission(id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction('submissions', 'readwrite');
        const store = transaction.objectStore('submissions');
        const request = store.delete(id);

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}

// Request Background Sync via Service Worker
function requestBackgroundSync() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then((reg) => {
            return reg.sync.register('form-submission-sync');
        }).catch((err) => {
            console.warn('Sync registration failed:', err);
        });
    }
}

// Get all offline queued items for current form
function getQueuedItems(slug) {
    return new Promise((resolve) => {
        const transaction = db.transaction('submissions', 'readonly');
        const store = transaction.objectStore('submissions');
        const request = store.getAll();

        request.onsuccess = () => {
            const items = request.result.filter(s => s.slug === slug);
            items.sort((a, b) => b.number - a.number); // newest first
            resolve(items);
        };
        request.onerror = () => resolve([]);
    });
}

// Get single submission
function getSubmission(id) {
    return new Promise((resolve) => {
        const transaction = db.transaction('submissions', 'readonly');
        const store = transaction.objectStore('submissions');
        const request = store.get(id);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => resolve(null);
    });
}

// Extract form data of a specific page
function getPageFormData(formEl, pageIdx) {
    const pageContainer = formEl.querySelector(`.form-page-container[data-page-index="${pageIdx}"]`);
    if (!pageContainer) return {};

    const inputs = pageContainer.querySelectorAll('input, select, textarea');
    const data = {};
    inputs.forEach(input => {
        if (!input.name) return;
        
        // Skip inputs hidden by conditional logic
        const wrapper = input.closest('.field-wrapper');
        if (wrapper && wrapper.style.display === 'none') {
            return;
        }

        const key = input.name;

        // Skip honeypots/security tokens
        if (['_token', '_hp_website', '_hp_time', '_method'].includes(key)) {
            return;
        }

        if (key.endsWith('[]')) {
            const cleanKey = key.slice(0, -2);
            if (!data[cleanKey]) {
                data[cleanKey] = [];
            }
            if (input.type === 'checkbox') {
                if (input.checked) data[cleanKey].push(input.value);
            } else {
                data[cleanKey].push(input.value);
            }
        } else {
            if (input.type === 'radio') {
                if (input.checked) data[key] = input.value;
            } else if (input.type === 'checkbox') {
                if (input.checked) data[key] = input.value;
            } else if (input.type === 'file') {
                // skip file data serialization to plain JSON objects
            } else {
                data[key] = input.value;
            }
        }
    });
    return data;
}

// Populate form inputs helper
function populateForm(formEl, fields) {
    formEl.reset();

    for (const [key, value] of Object.entries(fields)) {
        const inputs = formEl.querySelectorAll(`[name="${key}"], [name="${key}[]"]`);
        if (inputs.length === 0) continue;

        const first = inputs[0];
        if (first.type === 'radio') {
            inputs.forEach(input => {
                input.checked = (String(input.value) === String(value));
            });
        } else if (first.type === 'checkbox') {
            const vals = Array.isArray(value) ? value : [value];
            inputs.forEach(input => {
                input.checked = vals.includes(input.value);
            });
        } else if (first.tagName === 'SELECT') {
            first.value = value;
        } else {
            first.value = value;
        }
    }

    // Trigger visual evaluation
    evaluateFormLogic();
}

// Enter edit mode of offline cached submission
async function enterEditMode(id) {
    editingId = id;
    const entry = await getSubmission(id);
    if (!entry) return;

    populateForm(currentForm, entry.fields);

    // Show page 0 and hide others
    const pageContainers = currentForm.querySelectorAll('.form-page-container');
    pageContainers.forEach((el, idx) => {
        el.style.display = (idx === 0) ? 'block' : 'none';
    });
    window.currentPageIdx = 0;

    evaluateFormLogic();
    renderPanel();
}

// Exit edit mode
function exitEditMode() {
    editingId = null;
    currentForm.reset();
    
    // Show page 0 and hide others
    const pageContainers = currentForm.querySelectorAll('.form-page-container');
    pageContainers.forEach((el, idx) => {
        el.style.display = (idx === 0) ? 'block' : 'none';
    });
    window.currentPageIdx = 0;

    evaluateFormLogic();
    renderPanel();
}

// Build Submission Box Item on Left Panel
function createSubmissionBox(entry) {
    const box = document.createElement('div');
    box.dataset.entryId = entry.id;
    box.className = `p-4 border transition-all duration-300 rounded cursor-pointer relative group ${
        editingId === entry.id
            ? 'border-neutral-900 dark:border-neutral-100 bg-neutral-50 dark:bg-neutral-900'
            : 'border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-950 hover:border-neutral-400 dark:hover:border-neutral-600'
    }`;

    if (entry.status === 'syncing') {
        box.classList.add('animate-pulse', 'border-amber-400', 'dark:border-amber-500');
    } else if (entry.status === 'failed') {
        box.classList.add('border-red-400', 'dark:border-red-500');
    }

    const previewValues = [];
    let count = 0;
    for (const val of Object.values(entry.fields)) {
        if (val && typeof val === 'string' && val.trim() !== '') {
            previewValues.push(val);
            count++;
            if (count >= 2) break;
        }
    }
    const previewText = previewValues.join(', ') || 'Empty submission';
    const dateFormatted = new Date(entry.savedAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    box.innerHTML = `
        <div class="flex items-center justify-between mb-1 select-none">
            <span class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 flex items-center gap-1.5">
                ${entry.status === 'syncing' ? `
                    <svg class="animate-spin h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Syncing...
                ` : `
                    Submission #${entry.number}
                `}
                ${editingId === entry.id ? '<span class="text-[10px] uppercase font-bold text-neutral-900 dark:text-neutral-100 bg-neutral-200 dark:bg-neutral-800 px-1 rounded">Editing</span>' : ''}
            </span>
            <span class="text-[10px] text-neutral-400 dark:text-neutral-500">${dateFormatted}</span>
        </div>
        <p class="text-xs font-light text-neutral-600 dark:text-neutral-400 truncate pr-6">${previewText}</p>
        
        ${entry.status !== 'syncing' ? `
            <button class="delete-box-btn absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-red-50 dark:hover:bg-red-950/20 text-neutral-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" title="Delete offline draft">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        ` : ''}
    `;

    box.addEventListener('click', (e) => {
        if (e.target.closest('.delete-box-btn')) return;
        if (entry.status === 'syncing') return;
        enterEditMode(entry.id);
    });

    const deleteBtn = box.querySelector('.delete-box-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            if (confirm('Delete this queued offline submission?')) {
                if (editingId === entry.id) {
                    exitEditMode();
                }
                await deleteSubmission(entry.id);
                renderPanel();
                showToast('Submission deleted');
            }
        });
    }

    return box;
}

// Render Left Queue Panel UI
export async function renderPanel() {
    const panel = document.getElementById('queue-panel');
    const badge = document.getElementById('queue-badge');
    const badgeCount = document.getElementById('badge-count');
    const queueList = document.getElementById('queue-list');
    const queueCountBadge = document.getElementById('queue-count');

    if (!panel || !queueList) return;

    const items = await getQueuedItems(currentSlug);

    if (queueCountBadge) queueCountBadge.textContent = items.length;
    if (badgeCount) badgeCount.textContent = items.length;

    if (items.length > 0) {
        if (badge) badge.classList.remove('hidden');
        
        // Auto-open panel on offline if not manually closed
        const isOffline = !navigator.onLine;
        const manualStatus = sessionStorage.getItem('pwa_queue_panel_open');
        const shouldBeOpen = (manualStatus === 'true') || (isOffline && manualStatus !== 'false') || editingId;
        
        if (shouldBeOpen) {
            panel.classList.remove('hidden');
            panel.classList.add('flex');
            sessionStorage.setItem('pwa_queue_panel_open', 'true');
        } else {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
        }
    } else {
        if (!editingId) {
            panel.classList.add('hidden');
            panel.classList.remove('flex');
            if (badge) badge.classList.add('hidden');
            sessionStorage.removeItem('pwa_queue_panel_open');
        }
    }

    queueList.innerHTML = '';

    if (!editingId) {
        const nextNum = items.length > 0 ? items[0].number + 1 : 1;
        const currentCard = document.createElement('div');
        currentCard.className = 'p-4 border border-dashed border-neutral-300 dark:border-neutral-700 bg-transparent rounded text-center select-none';
        currentCard.innerHTML = `
            <span class="text-xs text-neutral-400 dark:text-neutral-500 font-medium">
                Fill Form for Entry #${nextNum}
            </span>
        `;
        queueList.appendChild(currentCard);
    } else {
        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'w-full py-2.5 px-4 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:text-neutral-900 dark:hover:text-neutral-100 border border-neutral-200 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded transition';
        cancelBtn.textContent = '✕ Cancel Editing / Create New Entry';
        cancelBtn.addEventListener('click', exitEditMode);
        queueList.appendChild(cancelBtn);
    }

    items.forEach((item) => {
        const box = createSubmissionBox(item);
        queueList.insertBefore(box, queueList.firstChild);
    });
}

// Sync all offline submissions to database
export async function syncAll() {
    if (!navigator.onLine) return;
    if (isPageSyncing) return;
    isPageSyncing = true;

    try {
        const items = await getQueuedItems(currentSlug);
        const pending = items.filter(s => s.status !== 'syncing').reverse();

        if (pending.length === 0) return;

        const syncStatus = document.getElementById('sync-status');
        if (syncStatus) syncStatus.textContent = '🔄 Syncing submissions...';

        let successCount = 0;
        let failCount = 0;

        for (const entry of pending) {
            const box = document.querySelector(`[data-entry-id="${entry.id}"]`);
            if (box) {
                box.className = 'p-4 border rounded animate-pulse border-amber-400 dark:border-amber-500 bg-white dark:bg-neutral-950 relative';
                const statusLabel = box.querySelector('.text-neutral-500');
                if (statusLabel) {
                    statusLabel.innerHTML = `
                        <svg class="animate-spin h-3.5 w-3.5 text-amber-500 mr-1.5 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Syncing...
                    `;
                }
            }

            try {
                const res = await fetch(`/f/${entry.slug}/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ fields: entry.fields })
                });

                if (res.ok) {
                    if (box) {
                        box.className = 'p-4 border border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20 rounded relative transition-all duration-300';
                        const statusLabel = box.querySelector('.text-neutral-500');
                        if (statusLabel) statusLabel.innerHTML = '<span class="text-emerald-500 font-semibold">✓ Synced!</span>';
                        
                        await sleep(800);
                        box.style.maxHeight = box.offsetHeight + 'px';
                        box.style.opacity = '1';
                        box.style.transition = 'all 400ms ease';
                        box.offsetHeight;
                        
                        box.style.maxHeight = '0px';
                        box.style.opacity = '0';
                        box.style.paddingTop = '0px';
                        box.style.paddingBottom = '0px';
                        box.style.marginTop = '0px';
                        box.style.marginBottom = '0px';
                        box.style.borderWidth = '0px';
                        box.style.overflow = 'hidden';

                        await sleep(400);
                        box.remove();
                    }
                    await deleteSubmission(entry.id);
                    successCount++;
                } else {
                    throw new Error('Sync request failed');
                }
            } catch (err) {
                console.error('Failed to sync submission:', entry.id, err);
                if (box) {
                    box.className = 'p-4 border border-red-500 bg-red-50/20 dark:bg-red-950/20 rounded relative';
                    const statusLabel = box.querySelector('.text-neutral-500');
                    if (statusLabel) statusLabel.innerHTML = '<span class="text-red-500 font-semibold">⚠ Sync failed</span>';
                }
                failCount++;
                break; // keep order by stopping sync loop on error
            }
        }

        await renderPanel();
        if (syncStatus) {
            const remaining = await getQueuedItems(currentSlug);
            syncStatus.textContent = remaining.length > 0 ? '⚠ Some syncs failed' : '✓ All submissions synced';
        }

        if (successCount > 0 && failCount === 0) {
            showToast('All saved submissions synced successfully! ✓');
        }
    } finally {
        isPageSyncing = false;
    }
}

// Toast Helper
export function showToast(message, type = 'success') {
    const toast = document.getElementById('sync-toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = `fixed bottom-6 right-6 z-9999 px-5 py-3 shadow-xl rounded text-sm text-white transition-all duration-300 transform translate-y-20 opacity-0 ${
        type === 'amber' ? 'bg-amber-500' : 'bg-neutral-900 dark:bg-neutral-100 dark:text-neutral-900'
    }`;

    setTimeout(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    }, 50);

    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 4000);
}

// Utility Sleep
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Evaluate field visibility and page conditional logic client-side
export function evaluateFormLogic() {
    const form = currentForm;
    if (!form) return;

    const wrappers = form.querySelectorAll('.field-wrapper[data-logic-trigger-field]');
    
    // Gather all current field values without checking input.disabled check so we get values even of temporarily disabled pages
    const flatData = {};
    Object.assign(flatData, window.formProgress);

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (!input.name) return;
        const id = input.name.replace('[]', '');
        if (input.type === 'radio') {
            if (input.checked) flatData[id] = input.value;
        } else if (input.type === 'checkbox') {
            if (!flatData[id]) flatData[id] = [];
            if (input.checked) flatData[id].push(input.value);
        } else {
            flatData[id] = input.value;
        }
    });

    // Evaluate Field-level conditional logic visibility
    wrappers.forEach(wrapper => {
        const action = wrapper.dataset.logicAction;
        const triggerField = wrapper.dataset.logicTriggerField;
        const triggerValue = wrapper.dataset.logicTriggerValue;

        const val = flatData[triggerField];
        let conditionMet = false;
        if (Array.isArray(val)) {
            conditionMet = val.includes(triggerValue);
        } else {
            conditionMet = String(val) === String(triggerValue);
        }

        const shouldShow = (action === 'show') ? conditionMet : !conditionMet;

        if (shouldShow) {
            wrapper.style.display = 'block';
        } else {
            wrapper.style.display = 'none';
        }
    });

    // Evaluate Page-level conditional logic to find visible pages
    const pages = window.formPages || [];
    const visiblePages = [];

    for (let i = 0; i < pages.length; i++) {
        const page = pages[i];
        if (!page.conditionalLogic) {
            visiblePages.push(i);
            continue;
        }

        const { triggerFieldId, triggerValue, action = 'show' } = page.conditionalLogic;
        if (!triggerFieldId) {
            visiblePages.push(i);
            continue;
        }

        const val = flatData[triggerFieldId];
        const conditionMet = Array.isArray(val)
            ? val.includes(String(triggerValue))
            : String(val) === String(triggerValue);
        const shouldShow = action === 'show' ? conditionMet : !conditionMet;

        if (shouldShow) {
            visiblePages.push(i);
        }
    }

    window.visiblePages = visiblePages;

    // Enable inputs on active page, disable them on non-active pages to prevent validation blocks
    const pageContainers = form.querySelectorAll('.form-page-container');
    pageContainers.forEach((container, idx) => {
        const isActive = (idx === window.currentPageIdx);
        const pageInputs = container.querySelectorAll('input, select, textarea');
        
        if (isActive) {
            pageInputs.forEach(el => {
                const wrapper = el.closest('.field-wrapper');
                const isHiddenByLogic = wrapper && wrapper.style.display === 'none';
                
                if (!isHiddenByLogic) {
                    el.disabled = false;
                    if (el.hasAttribute('data-was-required')) {
                        el.required = true;
                    }
                } else {
                    if (el.required) {
                        el.setAttribute('data-was-required', 'true');
                        el.required = false;
                    }
                    el.disabled = true;
                }
            });
        } else {
            pageInputs.forEach(el => {
                if (el.required) {
                    el.setAttribute('data-was-required', 'true');
                    el.required = false;
                }
                el.disabled = true;
            });
        }
    });

    // Update Step Progress Indicator steps and connection lines
    const stepsContainer = document.getElementById('steps-container');
    if (stepsContainer) {
        const stepWrappers = stepsContainer.querySelectorAll('.step-dot-wrapper');
        const progressLine = document.getElementById('progress-line');

        stepWrappers.forEach(wrapper => {
            const idx = parseInt(wrapper.dataset.stepIndex);
            if (visiblePages.includes(idx)) {
                wrapper.style.display = 'flex';
            } else {
                wrapper.style.display = 'none';
            }
        });

        const stepIndexInVisible = visiblePages.indexOf(window.currentPageIdx);

        visiblePages.forEach((pageIdx, stepIdx) => {
            const wrapper = stepsContainer.querySelector(`.step-dot-wrapper[data-step-index="${pageIdx}"]`);
            if (!wrapper) return;

            const dot = wrapper.querySelector('.step-dot');
            const title = wrapper.querySelector('.step-title');
            const isCompleted = stepIdx < stepIndexInVisible;
            const isActive = pageIdx === window.currentPageIdx;

            dot.className = "step-dot w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-semibold transition-all duration-300";
            title.className = "step-title text-[10px] font-semibold uppercase tracking-wider transition-colors duration-300";

            if (isActive) {
                dot.classList.add('border-neutral-900', 'bg-neutral-900', 'text-white', 'dark:border-neutral-100', 'dark:bg-neutral-100', 'dark:text-neutral-900', 'shadow-sm', 'scale-110');
                title.classList.add('text-neutral-900', 'dark:text-neutral-100');
                dot.innerHTML = stepIdx + 1;
            } else if (isCompleted) {
                dot.classList.add('border-neutral-900', 'bg-neutral-900', 'text-white', 'dark:border-neutral-100', 'dark:bg-neutral-100', 'dark:text-neutral-900');
                title.classList.add('text-neutral-400', 'dark:text-neutral-500');
                dot.innerHTML = `<svg class="w-3.5 h-3.5 text-white dark:text-neutral-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>`;
            } else {
                dot.classList.add('border-neutral-200', 'bg-white', 'dark:border-neutral-800', 'dark:bg-neutral-900', 'text-neutral-400', 'dark:text-neutral-500');
                title.classList.add('text-neutral-400', 'dark:text-neutral-500');
                dot.innerHTML = stepIdx + 1;
            }
        });

        if (progressLine) {
            const widthPercent = visiblePages.length > 1 ? (stepIndexInVisible / (visiblePages.length - 1)) * 100 : 0;
            progressLine.style.width = `${widthPercent}%`;
        }
    }

    // Update Navigation Buttons (Back / Next / Submit)
    const prevBtn = document.getElementById('prev-button');
    const submitBtn = document.getElementById('submit-button');
    if (prevBtn && submitBtn) {
        const currentIdxInVisible = visiblePages.indexOf(window.currentPageIdx);

        if (currentIdxInVisible > 0) {
            prevBtn.classList.remove('hidden');
        } else {
            prevBtn.classList.add('hidden');
        }

        if (currentIdxInVisible === visiblePages.length - 1) {
            submitBtn.textContent = editingId ? `Update & Save #${editingId} ✓` : 'Submit';
        } else {
            submitBtn.textContent = 'Next →';
        }
    }
}

// Display dynamic success card in view
function showOnlineSuccess(message) {
    const formEl = document.getElementById('public-form');
    if (formEl) formEl.style.display = 'none';

    const progressEl = document.getElementById('step-progress-wrapper');
    if (progressEl) progressEl.style.display = 'none';

    const mainArea = formEl.parentNode;
    const successContainer = document.createElement('div');
    successContainer.className = "bg-white dark:bg-neutral-950 border border-emerald-200 dark:border-emerald-800 p-8 shadow-sm rounded-lg text-center space-y-4 animate-fade-in";
    successContainer.innerHTML = `
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 mb-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Submission Recorded</h2>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 font-light leading-relaxed max-w-md mx-auto">${message}</p>
        <div class="pt-4">
            <button id="submit-another-btn" class="px-5 py-2.5 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 text-sm font-medium hover:opacity-90 transition rounded">
                Submit Another Response
            </button>
        </div>
    `;
    mainArea.appendChild(successContainer);

    const submitAnotherBtn = successContainer.querySelector('#submit-another-btn');
    if (submitAnotherBtn) {
        submitAnotherBtn.addEventListener('click', () => {
            window.location.reload();
        });
    }
}

// Handle the final offline page submission (single-page or last page of multi-page)
async function handleOfflineLastPageSubmit(formEl) {
    let allFields = {};

    // For multi-page forms, merge all page fields from the DOM
    if (window.formPages && window.formPages.length > 1) {
        const visiblePages = window.visiblePages || [];
        visiblePages.forEach(pageIdx => {
            const pageData = getPageFormData(formEl, pageIdx);
            Object.assign(allFields, pageData);
        });
    } else {
        Object.assign(allFields, getPageFormData(formEl, 0));
    }

    if (editingId) {
        await updateSubmissionWithFields(editingId, allFields);
        showToast('Submission updated ✓');
        exitEditMode();
    } else {
        const savedId = await saveSubmissionWithFields(currentSlug, allFields);
        const item = await getSubmission(savedId);
        showToast(`Saved locally (Submission #${item.number}) ✓`, 'amber');

        clearOfflineDraftPages(currentSlug);
        formEl.reset();

        // Reset page display back to first page
        const pageContainers = formEl.querySelectorAll('.form-page-container');
        pageContainers.forEach((el, idx) => {
            el.style.display = (idx === 0) ? 'block' : 'none';
        });
        window.currentPageIdx = 0;

        evaluateFormLogic();
    }
    await renderPanel();
}

// Initialize Offline PWA Module
export async function init(slug, formEl) {
    currentSlug = slug;
    currentForm = formEl;

    if (!formEl) return;

    try {
        await initDb();
    } catch (err) {
        console.error('Offline queue module disabled due to DB initialization error');
        return;
    }

    updateOfflineStatus();
    await renderPanel();

    // Setup Back button listener
    const prevBtn = document.getElementById('prev-button');
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const visiblePages = window.visiblePages || [];
            const currentIdxInVisible = visiblePages.indexOf(window.currentPageIdx);
            if (currentIdxInVisible > 0) {
                const prevIdx = visiblePages[currentIdxInVisible - 1];
                const activeContainer = formEl.querySelector(`.form-page-container[data-page-index="${window.currentPageIdx}"]`);
                const prevContainer = formEl.querySelector(`.form-page-container[data-page-index="${prevIdx}"]`);
                
                if (activeContainer) activeContainer.style.display = 'none';
                if (prevContainer) prevContainer.style.display = 'block';
                
                window.currentPageIdx = prevIdx;
                evaluateFormLogic();
            }
        });
    }

    // Bind form submit interception for pagination and submission
    formEl.addEventListener('submit', async (event) => {
        event.preventDefault();

        // HTML5 Validation for inputs on the current page only
        const activeContainer = formEl.querySelector(`.form-page-container[data-page-index="${window.currentPageIdx}"]`);
        if (!activeContainer) return;

        const inputs = activeContainer.querySelectorAll('input, select, textarea');
        let isValid = true;
        for (let input of inputs) {
            if (!input.checkValidity()) {
                input.reportValidity();
                isValid = false;
                break;
            }
        }
        if (!isValid) return;

        const isOffline = !navigator.onLine;
        const visiblePages = window.visiblePages || [];
        const currentIdxInVisible = visiblePages.indexOf(window.currentPageIdx);
        const isLastPage = (currentIdxInVisible === visiblePages.length - 1);

        if (!isLastPage) {
            // "Next" transition
            const nextIdx = visiblePages[currentIdxInVisible + 1];
            const pageData = getPageFormData(formEl, window.currentPageIdx);

            if (isOffline) {
                // Offline progress saved locally
                saveOfflineDraftPage(currentSlug, window.currentPageIdx, pageData);
            } else {
                // Online progress sent to server in background
                const formData = new FormData();
                formData.append('_token', formEl.querySelector('[name="_token"]').value);
                formData.append('_hp_time', formEl.querySelector('[name="_hp_time"]').value);
                const hpWeb = formEl.querySelector('[name="_hp_website"]');
                if (hpWeb) formData.append('_hp_website', hpWeb.value);

                for (const [key, value] of Object.entries(pageData)) {
                    if (Array.isArray(value)) {
                        value.forEach(v => formData.append(`${key}[]`, v));
                    } else {
                        formData.append(key, value);
                    }
                }

                // Append file uploads if they exist on the current page
                inputs.forEach(input => {
                    if (input.type === 'file' && input.files.length > 0) {
                        formData.append(input.name, input.files[0]);
                    }
                });

                const pageNum = window.currentPageIdx + 1;
                fetch(`/f/${currentSlug}/page/${pageNum}`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                }).catch(err => console.error('[Background Save Page Error]', err));
            }

            // Transition page visually instantaneously
            activeContainer.style.display = 'none';
            const nextContainer = formEl.querySelector(`.form-page-container[data-page-index="${nextIdx}"]`);
            if (nextContainer) nextContainer.style.display = 'block';

            window.currentPageIdx = nextIdx;
            evaluateFormLogic();
        } else {
            // "Final Submit"
            if (isOffline) {
                try {
                    await handleOfflineLastPageSubmit(formEl);
                } catch (err) {
                    console.error('[PWA] Offline submission error:', err);
                    showToast('Failed to save submission locally', 'amber');
                }
            } else {
                // Online submission via background fetch
                const submitBtn = document.getElementById('submit-button');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                }

                const pageData = getPageFormData(formEl, window.currentPageIdx);
                const formData = new FormData();
                formData.append('_token', formEl.querySelector('[name="_token"]').value);
                formData.append('_hp_time', formEl.querySelector('[name="_hp_time"]').value);
                const hpWeb = formEl.querySelector('[name="_hp_website"]');
                if (hpWeb) formData.append('_hp_website', hpWeb.value);

                for (const [key, value] of Object.entries(pageData)) {
                    if (Array.isArray(value)) {
                        value.forEach(v => formData.append(`${key}[]`, v));
                    } else {
                        formData.append(key, value);
                    }
                }

                inputs.forEach(input => {
                    if (input.type === 'file' && input.files.length > 0) {
                        formData.append(input.name, input.files[0]);
                    }
                });

                try {
                    const response = await fetch(`/f/${currentSlug}`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });

                    const resData = await response.json();

                    if (response.ok && resData.success) {
                        clearOfflineDraftPages(currentSlug);
                        showOnlineSuccess(resData.message || 'Form submitted successfully!');
                    } else {
                        alert(resData.message || 'An error occurred during submission.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit';
                        }
                    }
                } catch (err) {
                    console.error('[Online Submit Error]', err);
                    alert('Network error. Saving locally instead.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit';
                    }
                    await handleOfflineLastPageSubmit(formEl);
                }
            }
        }
    });

    // Listen to form input changes to evaluate conditional logic in real-time
    formEl.addEventListener('change', evaluateFormLogic);
    formEl.addEventListener('input', evaluateFormLogic);

    // Run initial form logic evaluation
    evaluateFormLogic();

    // Listen to network change events
    window.addEventListener('online', async () => {
        updateOfflineStatus();
        
        const items = await getQueuedItems(currentSlug);
        if (items.length > 0) {
            const restoredBanner = document.getElementById('restored-banner');
            if (restoredBanner) {
                restoredBanner.classList.remove('hidden');
                setTimeout(() => {
                    restoredBanner.classList.add('hidden');
                }, 5000);
            }
            await syncAll();
        }
    });

    window.addEventListener('offline', () => {
        updateOfflineStatus();
    });

    // Listen to background sync notifications from service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', async (event) => {
            if (event.data) {
                const { type, number } = event.data;
                if (type === 'SUBMISSION_SYNCED') {
                    showToast(`Background: Submission #${number} synced successfully ✓`);
                    await renderPanel();
                } else if (type === 'SUBMISSION_FAILED_VALIDATION') {
                    showToast(`Background: Submission #${number} failed validation. Removed.`, 'amber');
                    await renderPanel();
                } else if (type === 'TRIGGER_PAGE_SYNC') {
                    const restoredBanner = document.getElementById('restored-banner');
                    if (restoredBanner && restoredBanner.classList.contains('hidden')) {
                        restoredBanner.classList.remove('hidden');
                        setTimeout(() => {
                            restoredBanner.classList.add('hidden');
                        }, 5000);
                    }
                    await syncAll();
                }
            }
        });
    }
}

// Update connectivity status visual warnings
function updateOfflineStatus() {
    const banner = document.getElementById('offline-banner');
    const fileWarnings = document.querySelectorAll('.file-offline-warning');
    const syncStatus = document.getElementById('sync-status');

    if (navigator.onLine) {
        if (banner) banner.classList.add('hidden');
        fileWarnings.forEach(w => w.classList.add('hidden'));
        if (syncStatus) syncStatus.textContent = 'Connected';
    } else {
        if (banner) banner.classList.remove('hidden');
        fileWarnings.forEach(w => w.classList.remove('hidden'));
        if (syncStatus) syncStatus.textContent = 'Offline — submissions saved locally';
    }
}

// Run initialization tasks
function runInitialization() {
    const slug = window.formSlug;
    const formEl = document.getElementById('public-form');
    
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (isStandalone) {
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn) {
            installBtn.classList.add('hidden');
            installBtn.style.display = 'none';
        }
    }

    if (slug && formEl) {
        registerSW();
        init(slug, formEl);
        
        const badge = document.getElementById('queue-badge');
        const panel = document.getElementById('queue-panel');
        if (badge && panel) {
            badge.addEventListener('click', () => {
                if (panel.classList.contains('hidden')) {
                    panel.classList.remove('hidden');
                    panel.classList.add('flex');
                    sessionStorage.setItem('pwa_queue_panel_open', 'true');
                } else {
                    panel.classList.add('hidden');
                    panel.classList.remove('flex');
                    sessionStorage.setItem('pwa_queue_panel_open', 'false');
                }
            });
        }
    }
}

// Check document ready state to execute initialization without module load delay
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runInitialization);
} else {
    runInitialization();
}
