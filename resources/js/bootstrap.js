/**
 * CSRF helpers shared by admin / POS / storefront (fetch-based, no axios).
 */

window.syncCsrfToken = function syncCsrfToken(tokenFromServer) {
    const meta = document.head.querySelector('meta[name="csrf-token"]');
    const token = tokenFromServer || meta?.content;
    if (!token) return null;

    if (meta) {
        meta.content = token;
    }

    document.querySelectorAll('input[name="_token"]').forEach((input) => {
        input.value = token;
    });

    return token;
};

window.currentCsrfToken = function currentCsrfToken() {
    return document.head.querySelector('meta[name="csrf-token"]')?.content || '';
};

window.refreshCsrfToken = async function refreshCsrfToken() {
    const endpoints = ['/csrf-token', '/refresh-session'];

    for (const endpoint of endpoints) {
        try {
            const res = await fetch(endpoint, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                cache: 'no-store',
            });
            if (!res.ok) continue;
            const data = await res.json();
            if (data?.csrf_token) {
                return window.syncCsrfToken(data.csrf_token);
            }
        } catch (e) {
            // try next endpoint
        }
    }

    return window.syncCsrfToken();
};

/**
 * JSON POST/PUT/PATCH/DELETE with CSRF header + one automatic retry on 419.
 */
window.fetchJsonWithCsrf = async function fetchJsonWithCsrf(url, options = {}, retried = false) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    if (method !== 'GET' && method !== 'HEAD') {
        if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        headers['X-CSRF-TOKEN'] = window.currentCsrfToken();
    }

    const res = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        method,
        headers,
    });

    if (res.status === 419 && !retried) {
        let token = null;
        try {
            const data = await res.clone().json();
            token = data?.csrf_token || null;
        } catch (e) {
            // ignore
        }
        if (token) {
            window.syncCsrfToken(token);
        } else {
            await window.refreshCsrfToken();
        }
        return window.fetchJsonWithCsrf(url, options, true);
    }

    return res;
};

document.addEventListener('submit', () => {
    window.syncCsrfToken?.();
}, true);

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && typeof window.refreshCsrfToken === 'function') {
        window.refreshCsrfToken();
    }
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted && typeof window.refreshCsrfToken === 'function') {
        window.refreshCsrfToken();
    }
});
