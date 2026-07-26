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

window.refreshCsrfToken = async function refreshCsrfToken() {
    try {
        const res = await fetch('/refresh-session', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        if (!res.ok) return null;
        const data = await res.json();
        if (data?.csrf_token) {
            return window.syncCsrfToken(data.csrf_token);
        }
        return window.syncCsrfToken();
    } catch (e) {
        return null;
    }
};

document.addEventListener('submit', () => {
    window.syncCsrfToken?.();
}, true);

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && typeof window.refreshCsrfToken === 'function') {
        window.refreshCsrfToken();
    }
});
