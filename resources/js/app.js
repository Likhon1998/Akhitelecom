import './bootstrap';

import Alpine from 'alpinejs';
import { initAdminProgress } from './admin-progress';
import { initAdminFeedback } from './admin-feedback';

window.Alpine = Alpine;

function startAlpineWhenReady() {
    if (typeof window.storefrontCart === 'function') {
        Alpine.start();
        return;
    }

    // Blade defines storefrontCart at the end of <body>; retry briefly until it exists.
    let tries = 0;
    const timer = setInterval(() => {
        tries += 1;
        if (typeof window.storefrontCart === 'function') {
            clearInterval(timer);
            Alpine.start();
        } else if (tries > 100) {
            clearInterval(timer);
            console.error('storefrontCart failed to load');
            Alpine.start();
        }
    }, 20);
}

startAlpineWhenReady();

document.addEventListener('DOMContentLoaded', () => {
    initAdminProgress();
    initAdminFeedback();
});
