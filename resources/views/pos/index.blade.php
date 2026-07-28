<x-pos-layout>
<style>
@import url('https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap');

:root {
    --navy: #0b1b3a;
    --navy-2: #122647;
    --blue: #2563eb;
    --blue-soft: #eff6ff;
    --bg: #f4f6fb;
    --surface: #ffffff;
    --surface-2: #f8fafc;
    --border: #e8edf5;
    --text-1: #0f172a;
    --text-2: #475569;
    --text-3: #94a3b8;
    --green: #16a34a;
    --green-bg: #ecfdf5;
    --green-border: #a7f3d0;
    --red: #dc2626;
    --red-bg: #fef2f2;
    --red-border: #fecaca;
    --amber: #d97706;
    --amber-bg: #fffbeb;
    --amber-border: #fde68a;
    --teal: #2563eb;
    --teal-bg: #eff6ff;
    --teal-border: #bfdbfe;
    --radius: 14px;
    --font: Figtree, system-ui, sans-serif;
    --mono: ui-monospace, SFMono-Regular, Menlo, monospace;
}

[data-theme="dark"] {
    --navy: #060d1a;
    --navy-2: #0b1528;
    --bg: #0b1220;
    --surface: #111827;
    --surface-2: #1a2332;
    --border: #243044;
    --text-1: #f8fafc;
    --text-2: #94a3b8;
    --text-3: #64748b;
    --blue-soft: #0b1f44;
    --teal: #3b82f6;
    --teal-bg: #0b1f44;
    --teal-border: #1e3a8a;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
[x-cloak] { display: none !important; }

.pos-shell {
    height: 100%; height: 100dvh; width: 100%; min-width: 0;
    overflow: hidden;
    display: flex; flex-direction: column;
    background: var(--bg); font-family: var(--font); color: var(--text-1);
}

/* Counter fullscreen prompt */
.pos-fs-hint {
    flex-shrink: 0;
    background: linear-gradient(90deg, #1d4ed8, #2563eb);
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.12);
}
.pos-fs-hint-inner {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 8px 16px; flex-wrap: wrap;
}
.pos-fs-hint-inner strong { font-size: 13px; font-weight: 750; margin-right: 8px; }
.pos-fs-hint-inner span { font-size: 12.5px; opacity: .92; }
.pos-fs-hint-actions { display: flex; align-items: center; gap: 8px; }
.pos-fs-btn {
    border: 0; border-radius: 9px; padding: 7px 14px;
    background: #fff; color: #1d4ed8;
    font: inherit; font-size: 12px; font-weight: 750; cursor: pointer;
}
.pos-fs-btn:hover { background: #eff6ff; }
.pos-fs-dismiss {
    border: 1px solid rgba(255,255,255,.35); border-radius: 9px; padding: 7px 12px;
    background: transparent; color: #fff;
    font: inherit; font-size: 12px; font-weight: 650; cursor: pointer;
}
.pos-fs-dismiss:hover { background: rgba(255,255,255,.1); }

/* ── Top bar ── */
.pos-chrome {
    flex-shrink: 0; height: 58px;
    display: flex; align-items: center; gap: 14px;
    padding: 0 16px;
    background: var(--navy); color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.pos-brand {
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    text-decoration: none; color: #fff; font-weight: 700; font-size: 14px;
}
.pos-brand-mark {
    width: 34px; height: 34px; border-radius: 10px;
    background: var(--blue); display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 18px rgba(37,99,235,.35);
}
.pos-brand-mark svg { width: 17px; height: 17px; }
.pos-brand em { font-style: normal; color: #93c5fd; font-weight: 600; }

.pos-chrome-search {
    flex: 1; max-width: 560px; margin: 0 auto; position: relative;
}
.pos-chrome-search .search-icon {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    width: 15px; height: 15px; color: #94a3b8; pointer-events: none;
}
.pos-chrome-search .search-input {
    width: 100%;
    padding: 10px 78px 10px 38px;
    border-radius: 12px; border: 1px solid rgba(255,255,255,.08);
    background: var(--navy-2); color: #fff;
    font-family: var(--font); font-size: 13px; outline: none;
}
.pos-chrome-search .search-input::placeholder { color: #64748b; }
.pos-chrome-search .search-input:focus {
    border-color: rgba(37,99,235,.55); box-shadow: 0 0 0 3px rgba(37,99,235,.2);
}
.pos-chrome-search .kbd {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    font-size: 10px; font-weight: 700; color: #64748b;
    border: 1px solid rgba(255,255,255,.1); border-radius: 6px; padding: 2px 7px;
}

.pos-chrome-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto; }
.pos-tool-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,.1); background: transparent;
    color: #e2e8f0; font-size: 12px; font-weight: 650; cursor: pointer; font-family: var(--font);
}
.pos-tool-btn:hover { background: rgba(255,255,255,.06); }
.pos-tool-btn.is-active { background: rgba(37,99,235,.25); border-color: rgba(96,165,250,.45); color: #93c5fd; }
.pos-tool-btn svg { width: 15px; height: 15px; }
.pos-close-day {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 12px; border-radius: 10px;
    border: 1px solid rgba(248,113,113,.35); background: rgba(239,68,68,.12);
    color: #fecaca; text-decoration: none; font-size: 12px; font-weight: 700;
}
.pos-close-day svg { width: 14px; height: 14px; }
.pos-user {
    display: flex; align-items: center; gap: 8px;
    padding-left: 8px; border-left: 1px solid rgba(255,255,255,.1);
}
.pos-user-avatar {
    width: 32px; height: 32px; border-radius: 999px;
    background: #1d4ed8; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
}
.pos-user-meta { line-height: 1.2; }
.pos-user-name { font-size: 12.5px; font-weight: 650; color: #fff; }
.pos-user-role { font-size: 11px; color: #94a3b8; }
.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    border: 1px solid transparent;
}
.status-badge.online  { background: rgba(22,163,74,.15); color: #86efac; border-color: rgba(22,163,74,.25); }
.status-badge.offline { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.25); }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ── Body: rail + workspace ── */
.pos-body {
    flex: 1 1 0; min-height: 0;
    display: grid;
    grid-template-columns: 84px minmax(0, 1fr);
    overflow: hidden;
}
.pos-rail {
    background: #eef2f7;
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column; align-items: center;
    padding: 12px 8px; gap: 4px; min-height: 0;
}
.pos-rail a, .pos-rail button {
    width: 100%;
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    padding: 10px 4px; border-radius: 12px;
    text-decoration: none; color: var(--text-2);
    font-size: 10px; font-weight: 650; border: none; background: transparent;
    cursor: pointer; font-family: var(--font);
}
.pos-rail a:hover, .pos-rail button:hover { background: #fff; color: var(--text-1); }
.pos-rail a.active {
    background: var(--blue); color: #fff;
    box-shadow: 0 8px 18px rgba(37,99,235,.28);
}
.pos-rail svg { width: 18px; height: 18px; }
.pos-rail-foot {
    margin-top: auto; width: 100%;
    padding-top: 8px; border-top: 1px solid var(--border);
}
.pos-rail-online {
    font-size: 9px; font-weight: 700; color: var(--green);
    display: flex; align-items: center; justify-content: center; gap: 4px;
}

.pos-root {
    flex: 1 1 0; min-height: 0; min-width: 0;
    padding: 12px;
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(360px, .95fr);
    gap: 12px; overflow: hidden;
}

.panel-left, .panel-right {
    min-width: 0; min-height: 0; height: 100%;
    display: flex; flex-direction: column;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04);
    overflow: hidden;
}
.panel-left { overflow: visible; }
.panel-right {
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
}

.panel-top {
    padding: 12px 14px 10px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0; background: var(--surface);
    overflow: visible;
    position: relative;
    z-index: 25;
}
.top-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.icon-btn {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--surface-2); border: 1px solid var(--border);
    color: var(--text-2); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.icon-btn:hover { color: var(--text-1); background: #eef2f7; }
.icon-btn svg { width: 16px; height: 16px; }

.filter-label {
    font-size: 10px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
    color: var(--text-3); margin-bottom: 6px;
}
.cat-bar {
    display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-start;
}
.cat-item {
    position: relative; z-index: 1;
}
.cat-item.open { z-index: 40; }
.cat-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 7px 11px; border-radius: 10px; cursor: pointer;
    border: 1px solid var(--border); background: var(--surface-2);
    font-family: var(--font); font-size: 12.5px; font-weight: 700; color: var(--text-2);
    white-space: nowrap;
}
.cat-btn:hover, .cat-item.open .cat-btn {
    border-color: #93c5fd; background: #eff6ff; color: #1d4ed8;
}
.cat-btn.active {
    background: var(--blue); border-color: var(--blue); color: #fff;
}
.cat-btn.active .cat-btn-count {
    background: rgba(255,255,255,.2); border-color: transparent; color: #fff;
}
.cat-btn-count {
    font-family: var(--mono); font-size: 10.5px; font-weight: 700;
    color: var(--text-3); background: #fff; border: 1px solid var(--border);
    border-radius: 999px; padding: 1px 6px; min-width: 22px; text-align: center;
}
.cat-btn-chevron {
    width: 12px; height: 12px; opacity: .7; transition: transform .15s;
}
.cat-item.open .cat-btn-chevron { transform: rotate(180deg); }
.cat-brand-menu {
    position: absolute; top: calc(100% + 4px); left: 0; min-width: 200px;
    background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
    box-shadow: 0 12px 28px rgba(15,23,42,.14); padding: 4px 0; z-index: 50;
}
.cat-brand-menu-title {
    padding: 6px 12px 4px; font-size: 10px; font-weight: 800; letter-spacing: .06em;
    text-transform: uppercase; color: var(--text-3);
}
.cat-brand-item {
    width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 8px 12px; border: none; background: transparent; cursor: pointer;
    font-family: var(--font); font-size: 12.5px; font-weight: 650; color: var(--text-1); text-align: left;
}
.cat-brand-item:hover { background: #f1f5f9; }
.cat-brand-item.active { background: #f0fdfa; color: #0f766e; }
.p-brand-tag {
    font-size: 10px; font-weight: 700; color: var(--text-3);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.product-list {
    flex: 1 1 0; min-height: 0; overflow-y: auto;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
    gap: 8px;
    background: var(--bg);
    align-content: start;
    scrollbar-width: none;
}
.product-list::-webkit-scrollbar { display: none; }

.product-card {
    position: relative;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0;
    cursor: pointer;
    display: flex; flex-direction: column; overflow: hidden;
    transition: border-color .15s, box-shadow .15s, transform .1s;
}
.product-card:hover {
    border-color: #bfdbfe;
    box-shadow: 0 8px 24px rgba(37,99,235,.08);
    transform: translateY(-1px);
}
.product-card:active { transform: scale(.985); }
.product-card.out-of-stock { opacity: .45; cursor: not-allowed; pointer-events: none; filter: grayscale(.3); }
.product-card.in-cart {
    border-color: var(--blue);
    background: linear-gradient(180deg, #eff6ff 0%, #fff 55%);
    box-shadow: 0 0 0 2px rgba(37,99,235,.28), 0 8px 20px rgba(37,99,235,.1);
}
.product-card.in-cart .p-img-wrap {
    background: #dbeafe;
    border-bottom-color: #bfdbfe;
}
.product-card.in-cart .p-name { color: #1e3a8a; }
.p-selected-badge {
    position: absolute; top: 6px; right: 6px; z-index: 2;
    min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px;
    background: var(--blue); color: #fff;
    font-family: var(--mono); font-size: 11px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 10px rgba(37,99,235,.4);
}
.p-in-cart-tag {
    position: absolute; top: 6px; left: 6px; z-index: 2;
    font-size: 9px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
    color: #1d4ed8; background: #fff; border: 1px solid #93c5fd;
    border-radius: 999px; padding: 2px 6px;
}
.p-img-wrap {
    width: 100%; height: 72px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.p-img-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.p-avatar {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 700; color: var(--text-3);
    background: linear-gradient(180deg, #f8fafc, #eef2f7);
}
.p-body { padding: 8px 9px 10px; display: flex; flex-direction: column; gap: 4px; flex: 1; }
.p-name {
    font-size: 12px; font-weight: 650; color: var(--text-1); line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.4em;
}
.p-foot { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; margin-top: auto; }
.p-price { font-size: 13px; font-weight: 700; color: var(--text-1); }
.p-price-sym { font-size: 10px; color: var(--text-3); margin-right: 2px; font-weight: 650; }
.stock-chip {
    font-size: 10.5px; font-weight: 650; padding: 3px 8px;
    border-radius: 999px; border: 1px solid; white-space: nowrap;
}
.stock-ok  { background: var(--green-bg); color: var(--green); border-color: var(--green-border); }
.stock-low { background: var(--amber-bg); color: var(--amber); border-color: var(--amber-border); }
.stock-out { background: var(--red-bg); color: var(--red); border-color: var(--red-border); }

/* Cart */
.cart-head {
    padding: 10px 12px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
    position: sticky; top: 0; z-index: 3; background: var(--surface);
}
.cart-title { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; }
.cart-badge-icon {
    width: 26px; height: 26px; border-radius: 8px;
    background: var(--blue-soft); border: 1px solid var(--teal-border);
    display: flex; align-items: center; justify-content: center;
}
.cart-badge-icon svg { width: 13px; height: 13px; color: var(--blue); }
.cart-count {
    min-width: 18px; height: 18px; padding: 0 5px; border-radius: 999px;
    background: var(--blue); color: #fff;
    font-family: var(--mono); font-size: 10px; font-weight: 600;
    display: inline-flex; align-items: center; justify-content: center;
}
.cart-head-actions { display: flex; align-items: center; gap: 6px; }
.held-btn, .clear-btn {
    padding: 5px 8px; border-radius: 8px; font-size: 11px; font-weight: 650;
    cursor: pointer; font-family: var(--font); border: 1px solid transparent;
}
.held-btn {
    display: flex; align-items: center; gap: 4px;
    background: var(--amber-bg); color: var(--amber); border-color: var(--amber-border);
}
.held-btn svg { width: 12px; height: 12px; }
.clear-btn { background: transparent; color: var(--red); }
.clear-btn:hover { background: var(--red-bg); border-color: var(--red-border); }

.exchange-banner {
    padding: 7px 12px; flex-shrink: 0;
    background: var(--amber-bg); border-bottom: 1px solid var(--amber-border);
    display: flex; justify-content: space-between; align-items: center;
    font-size: 11px; font-weight: 650; color: var(--amber);
}

.cart-items {
    flex: 0 0 auto; min-height: 110px;
    padding: 8px 10px; display: flex; flex-direction: column; gap: 6px;
    background: #f8fafc;
}
.cart-items-label {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 2px 2px;
    font-size: 10px; font-weight: 800; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .05em; flex-shrink: 0;
}
.cart-items-label strong { color: var(--blue); font-family: var(--mono); font-weight: 700; }

.cart-item {
    position: relative;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 8px;
    display: grid;
    grid-template-columns: 40px 1fr auto;
    grid-template-rows: auto auto;
    gap: 4px 8px;
    align-items: center;
    box-shadow: 0 1px 2px rgba(15,23,42,.04);
}
.cart-item.just-added {
    border-color: var(--blue);
    background: #eff6ff;
    animation: cartPulse .6s ease;
}
@keyframes cartPulse {
    0% { box-shadow: 0 0 0 0 rgba(37,99,235,.45); }
    100% { box-shadow: 0 0 0 8px rgba(37,99,235,0); }
}
.cart-item.at-limit { border-color: var(--amber-border); background: var(--amber-bg); }
.ci-thumb {
    grid-row: 1 / 3; width: 40px; height: 40px; border-radius: 8px;
    background: var(--surface-2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.ci-thumb img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
.ci-thumb span { font-size: 10px; font-weight: 800; color: var(--text-3); }
.ci-index { display: none; }
.ci-info { min-width: 0; grid-column: 2 / 3; }
.ci-name { font-size: 12px; font-weight: 700; color: var(--text-1); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-unit { font-size: 10.5px; color: var(--text-3); margin-top: 1px; font-family: var(--mono); }
.ci-sub { font-family: var(--mono); font-size: 12px; font-weight: 700; text-align: right; color: var(--blue); grid-column: 3 / 4; grid-row: 1 / 2; }
.qty-ctrl {
    display: flex; align-items: center;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 8px; overflow: hidden; grid-column: 2 / 3; width: fit-content;
}
.qty-btn {
    width: 26px; height: 26px; border: none; background: none;
    color: var(--text-2); font-size: 14px; font-weight: 700; cursor: pointer;
}
.qty-btn:hover { background: #eef2f7; color: var(--text-1); }
.qty-num {
    width: 26px; text-align: center;
    font-family: var(--mono); font-size: 12px; font-weight: 700; color: var(--text-1);
}
.ci-remove {
    width: 24px; height: 24px; border: none; border-radius: 6px;
    background: transparent; color: var(--text-3); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    grid-column: 3 / 4; grid-row: 2 / 3; justify-self: end;
}
.ci-remove:hover { color: var(--red); background: var(--red-bg); }

.cart-empty {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-align: center; padding: 18px 12px; color: var(--text-3);
    min-height: 100px;
}
.empty-ring {
    width: 44px; height: 44px; border-radius: 50%;
    border: 1.5px dashed #cbd5e1; background: var(--surface);
    display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
}
.empty-ring svg { width: 18px; height: 18px; }
.empty-title { font-size: 12.5px; font-weight: 650; color: var(--text-2); }
.empty-sub { font-size: 11px; margin-top: 3px; }

.cart-extras {
    flex-shrink: 0; border-top: 1px solid var(--border); background: var(--surface-2);
}
.extras-body, .extras-body.always-open {
    padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 6px;
}
.field-label {
    display: block; margin-bottom: 3px;
    font-size: 10px; font-weight: 700; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .04em;
}
.extras-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
}
.cust-phone-wrap { position: relative; }
.cust-input, .discount-input, .coupon-input {
    width: 100%; padding: 7px 8px;
    border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-1);
    font-family: var(--font); font-size: 12px; outline: none;
}
.cust-input:focus, .discount-input:focus, .coupon-input:focus {
    border-color: var(--blue); box-shadow: 0 0 0 2px rgba(37,99,235,.12);
}
.discount-row, .coupon-row { display: flex; gap: 5px; align-items: center; }
.type-toggle {
    display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; flex-shrink: 0;
}
.tt-btn {
    padding: 6px 8px; border: none; cursor: pointer;
    background: var(--surface); color: var(--text-3);
    font-family: var(--mono); font-size: 11px; font-weight: 650;
}
.tt-btn.active { background: var(--blue); color: #fff; }
.discount-input { flex: 1; font-family: var(--mono); font-weight: 600; min-width: 0; }
.coupon-input { flex: 1; font-family: var(--mono); text-transform: uppercase; min-width: 0; }
.coupon-apply-btn {
    padding: 7px 10px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface); color: var(--text-2);
    font-size: 11px; font-weight: 650; cursor: pointer; font-family: var(--font);
}
.coupon-apply-btn:hover { color: var(--blue); border-color: var(--teal-border); background: var(--blue-soft); }
.coupon-applied {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 8px; border-radius: 8px;
    background: var(--green-bg); border: 1px solid var(--green-border);
    font-size: 11px; font-weight: 650; color: var(--green);
}
.coupon-remove { border: none; background: none; color: var(--red); font-weight: 650; cursor: pointer; font-size: 11px; }
.discount-badge {
    flex-shrink: 0; font-family: var(--mono); font-size: 10.5px; font-weight: 650;
    color: var(--green); background: var(--green-bg);
    border: 1px solid var(--green-border); padding: 5px 6px; border-radius: 7px;
}

.cart-foot {
    flex-shrink: 0; padding: 10px 12px 12px;
    border-top: 1px solid var(--border); background: var(--surface);
    position: sticky; bottom: 0; z-index: 3;
    box-shadow: 0 -6px 16px rgba(15,23,42,.04);
}
.summary-rows { display: flex; flex-direction: column; gap: 2px; margin-bottom: 6px; }
.sum-row { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-2); }
.sum-val { font-family: var(--mono); font-weight: 600; }
.sum-row.discount .sum-val, .sum-row.exchange .sum-val { color: var(--green); }
.total-row {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: 8px 0 10px; border-top: 1px dashed #dbe3ef;
}
.total-label { font-size: 12px; font-weight: 700; }
.total-val { font-family: var(--mono); font-size: 20px; font-weight: 700; color: var(--blue); }
.total-sym { font-size: 11px; color: var(--text-3); margin-right: 2px; }

.pay-methods { display: grid; grid-template-columns: 1.2fr .9fr .9fr; gap: 5px; margin-bottom: 6px; }
.pay-method {
    padding: 8px 6px; border-radius: 9px; cursor: pointer; font-family: var(--font);
    font-size: 12px; font-weight: 700; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2);
}
.pay-method.primary { background: var(--blue); color: #fff; border-color: var(--blue); }
.pay-method:hover:not(:disabled) { filter: brightness(.97); }
.pay-method:disabled { opacity: .45; cursor: not-allowed; }

.cart-actions { display: grid; grid-template-columns: 104px 1fr; gap: 8px; }
.hold-btn, .pay-btn {
    border-radius: 12px; cursor: pointer; font-family: var(--font); font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 6px; transition: .15s;
}
.hold-btn {
    padding: 14px 8px; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2); font-size: 13px;
}
.hold-btn:hover { background: #eef2f7; color: var(--text-1); }
.hold-btn:disabled { opacity: .4; cursor: not-allowed; }
.hold-btn svg { width: 15px; height: 15px; }
.pay-btn {
    padding: 14px; border: none; background: var(--green); color: #fff; font-size: 14px;
    box-shadow: 0 10px 22px rgba(22,163,74,.28);
    flex-direction: row; justify-content: space-between;
}
.pay-btn:hover { background: #15803d; }
.pay-btn:disabled { background: #cbd5e1; color: #64748b; box-shadow: none; cursor: not-allowed; }
.pay-btn .pay-hint {
    font-size: 10.5px; font-family: var(--mono); font-weight: 600;
    opacity: .9; background: rgba(255,255,255,.2); padding: 2px 7px; border-radius: 6px;
}

/* Modal / kb / toast — keep functional, blue-tint accents */
.modal-overlay {
    position: fixed; inset: 0; z-index: 80;
    display: flex; align-items: center; justify-content: center;
    padding: 12px;
}
.modal-bg { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(6px); }
.modal {
    position: relative; z-index: 1; width: 100%; max-width: 400px;
    max-height: min(92vh, 620px);
    display: flex; flex-direction: column;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; box-shadow: 0 20px 50px rgba(15,23,42,.22);
    overflow: hidden;
}
.modal-head {
    padding: 12px 14px; border-bottom: 1px solid var(--border);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    flex-shrink: 0; background: var(--surface);
}
.modal-title { font-size: 14px; font-weight: 700; letter-spacing: -.01em; }
.modal-subtitle { font-size: 11px; color: var(--text-3); margin-top: 2px; }
.modal-close {
    width: 28px; height: 28px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface-2); color: var(--text-2); cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.modal-close svg { width: 13px; height: 13px; }
.modal-body {
    padding: 12px 14px; display: flex; flex-direction: column; gap: 10px;
    overflow-y: auto; flex: 1 1 auto; min-height: 0;
    scrollbar-width: thin;
}
.amount-display {
    background: var(--blue-soft); border: 1px solid var(--teal-border);
    border-radius: 10px; padding: 10px 12px; text-align: center;
}
.amount-label { font-size: 10px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; }
.amount-value { font-family: var(--mono); font-size: 24px; font-weight: 700; margin-top: 2px; color: var(--blue); line-height: 1.2; }
.amount-value .ccy { font-size: 13px; color: var(--text-3); margin-right: 2px; font-weight: 600; }
.amount-note { font-size: 11px; font-weight: 650; margin-top: 3px; }
.warning-box {
    display: flex; gap: 7px; align-items: flex-start;
    background: var(--amber-bg); border: 1px solid var(--amber-border);
    border-radius: 10px; padding: 8px 10px;
}
.warning-box svg { width: 14px; height: 14px; color: var(--amber); flex-shrink: 0; margin-top: 1px; }
.warning-text { font-size: 11px; font-weight: 600; color: var(--amber); line-height: 1.4; }
.field-label, .field-label-pay { font-size: 10px; font-weight: 700; color: var(--text-2); text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 6px; }
.split-grid { display: flex; flex-direction: column; gap: 5px; }
.sp-row {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 9px; padding: 7px 10px;
}
.sp-row:focus-within { border-color: var(--blue); box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
.sp-label { font-size: 12px; font-weight: 650; display: flex; align-items: center; gap: 8px; }
.sp-input {
    width: 100px; text-align: right; border: none; outline: none; background: transparent;
    font-family: var(--mono); font-size: 14px; font-weight: 600; color: var(--text-1);
}
.sp-input::-webkit-inner-spin-button, .sp-input::-webkit-outer-spin-button { display: none; }
.qc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 6px; }
.qc-btn {
    padding: 7px 4px; border: 1px solid var(--border); border-radius: 8px;
    background: var(--surface); color: var(--text-2);
    font-family: var(--mono); font-size: 11px; font-weight: 650; cursor: pointer;
}
.qc-btn:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
.qc-btn.exact { background: var(--navy); color: #fff; border-color: var(--navy); }
.change-box {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 11px; border-radius: 9px; border: 1px solid;
}
.change-box.ok  { background: var(--green-bg); border-color: var(--green-border); }
.change-box.err { background: var(--red-bg); border-color: var(--red-border); }
.change-lbl { font-size: 11.5px; font-weight: 650; }
.change-box.ok .change-lbl { color: #166534; }
.change-box.err .change-lbl { color: var(--red); }
.change-val { font-family: var(--mono); font-size: 15px; font-weight: 700; }
.change-box.ok .change-val { color: var(--green); }
.change-box.err .change-val { color: var(--red); }
.err-hint { font-size: 11px; color: var(--red); text-align: center; font-weight: 650; margin-top: -2px; }
.pay-summary {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;
    border: 1px solid var(--border); border-radius: 10px; overflow: hidden;
    background: var(--surface);
}
.pay-summary-cell {
    padding: 8px 8px; text-align: center;
    border-right: 1px solid var(--border);
}
.pay-summary-cell:last-child { border-right: none; }
.pay-summary-cell .ps-label {
    font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--text-3);
}
.pay-summary-cell .ps-val {
    margin-top: 3px; font-family: var(--mono); font-size: 12.5px; font-weight: 700; color: var(--text-1);
}
.pay-summary-cell.due .ps-val { color: var(--blue); }
.pay-summary-cell.paid .ps-val { color: #0f172a; }
.pay-summary-cell.change.ok .ps-val { color: var(--green); }
.pay-summary-cell.change.err .ps-val { color: var(--red); }
.pay-summary-cell.change.less .ps-val { color: #b45309; }
.pay-summary-cell.change.less .ps-label { color: #b45309; }

.invoice-overlay { z-index: 90; }
.invoice-modal {
    max-width: 420px; max-height: min(94vh, 720px);
    display: flex; flex-direction: column;
}
.invoice-frame-wrap {
    background: #f1f5f9; border-bottom: 1px solid var(--border);
    flex: 1 1 auto; min-height: 0; overflow: hidden;
    display: flex; flex-direction: column;
}
.invoice-frame {
    width: 100%; flex: 1 1 auto; min-height: 360px; border: 0; background: #fff;
}
.invoice-meta {
    display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
    padding: 10px 14px; border-bottom: 1px solid var(--border); background: var(--surface-2);
    flex-shrink: 0;
}
.invoice-meta-item {
    background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 7px 8px;
}
.invoice-meta-item .im-label { font-size: 9px; font-weight: 800; color: var(--text-3); text-transform: uppercase; }
.invoice-meta-item .im-val { margin-top: 2px; font-size: 12px; font-weight: 700; font-family: var(--mono); color: var(--text-1); }
.invoice-meta-item.change .im-val { color: var(--green); }
.modal-foot {
    padding: 10px 14px 12px; display: flex; gap: 7px;
    flex-shrink: 0; border-top: 1px solid var(--border); background: var(--surface);
}
.modal-cancel, .modal-confirm {
    flex: 1; padding: 9px 10px; border-radius: 10px; cursor: pointer;
    font-family: var(--font); font-size: 12.5px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.modal-cancel { background: var(--surface-2); border: 1px solid var(--border); color: var(--text-2); }
.modal-confirm { background: var(--green); border: none; color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,.22); }
.modal-confirm:hover { background: #15803d; }
.modal-confirm:disabled { background: #cbd5e1; color: #64748b; box-shadow: none; cursor: not-allowed; }
.modal-confirm svg { width: 14px; height: 14px; }
.modal-kbd {
    font-size: 9px; font-family: var(--mono); font-weight: 700;
    padding: 1px 5px; border-radius: 4px; border: 1px solid currentColor; opacity: .75;
}

.kb-overlay {
    position: fixed; inset: 0; z-index: 100;
    background: rgba(15,23,42,.55); backdrop-filter: blur(10px);
    display: flex; align-items: center; justify-content: center; padding: 20px;
}
.kb-modal {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 18px; width: 100%; max-width: 460px; overflow: hidden;
    box-shadow: 0 20px 50px rgba(15,23,42,.2);
}
.kb-head {
    padding: 18px 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.kb-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.kb-title svg { width: 17px; height: 17px; color: var(--blue); }
.kb-grid { padding: 14px 16px 18px; display: flex; flex-direction: column; gap: 6px; }
.kb-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface-2);
}
.kb-desc { font-size: 13px; font-weight: 600; }
.key {
    font-family: var(--mono); font-size: 11px; font-weight: 650;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 6px; padding: 3px 8px; color: var(--text-1);
}

.toast-dock {
    position: fixed; bottom: 18px; right: 18px; z-index: 200;
    display: flex; flex-direction: column-reverse; gap: 7px;
    pointer-events: none; max-width: 320px;
}
.toast {
    display: flex; align-items: center; gap: 9px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 11px 14px;
    box-shadow: 0 16px 40px rgba(15,23,42,.14); pointer-events: all;
}
.toast-bar { width: 3px; height: 30px; border-radius: 2px; flex-shrink: 0; }
.toast-msg { font-size: 13px; font-weight: 600; line-height: 1.35; }
.toast.success .toast-bar { background: var(--green); }
.toast.error .toast-bar { background: var(--red); }
.toast.warning .toast-bar { background: var(--amber); }
.toast.info .toast-bar { background: var(--blue); }

/* Counter tills: keep side-by-side on large screens; stack earlier for tablets/phones. */
@media (max-width: 960px) {
    .pos-root {
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, 1fr);
        gap: 8px;
        padding: 8px;
    }
    .pos-chrome-search { max-width: 420px; }
    .pos-tool-btn span.pos-tool-label { display: none; }
}
@media (max-width: 768px) {
    .pos-rail { width: 64px; }
    .pos-rail a span, .pos-rail button span { font-size: 9px; }
    .pos-body { grid-template-columns: 64px minmax(0, 1fr); }
    .pos-root {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(40vh, 1fr) minmax(46vh, 1.05fr);
        gap: 6px;
    }
    .pos-user-meta, .pos-brand span:not(.pos-brand-mark) { display: none; }
    .product-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .p-img-wrap { height: 84px; }
    .cart-actions { grid-template-columns: 1fr; }
    .modal { width: min(100vw - 16px, 480px) !important; max-height: min(92vh, 720px); }
}
@media (max-width: 480px) {
    .pos-rail { display: none; }
    .pos-body { grid-template-columns: 1fr; }
    .pos-root {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(36vh, 1fr) minmax(50vh, 1.15fr);
        padding: 6px;
    }
    .product-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
}
</style>

<div class="pos-shell"
     x-data="posSystem()"
     :data-theme="darkMode ? 'dark' : 'light'"
     @keydown.window="handleKeydown($event)">

        <div class="pos-chrome">
        <a href="{{ route('dashboard') }}" class="pos-brand" title="Dashboard">
            <span class="pos-brand-mark">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </span>
            <span>{{ Auth::user()->shop->name ?? 'GAGET' }} <em>POS</em></span>
        </a>

        <div class="pos-chrome-search">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" x-model="search" x-ref="searchInput" autofocus
                   placeholder="Scan barcode or search name / SKU..."
                   class="search-input"
                   @input="onSearchInput()"
                   @keydown.enter.prevent="onSearchEnter()">
            <span class="kbd">Scan</span>
        </div>

        <div class="pos-chrome-right">
            <button type="button" class="pos-tool-btn" @click="$refs.searchInput.focus()" title="Focus barcode / search">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6.5 0a5.5 5.5 0 11-11 0 5.5 5.5 0 0111 0zM4 8V6a2 2 0 012-2h2m8 0h2a2 2 0 012 2v2"/></svg>
                <span class="pos-tool-label">Scan</span>
            </button>
            <button type="button" class="pos-tool-btn" @click="kbOpen = true" title="Keyboard shortcuts">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <button type="button" class="pos-tool-btn" @click="toggleFullscreen()"
                    :title="isFullscreen ? 'Exit fullscreen (F11)' : 'Fullscreen (F11)'"
                    :class="isFullscreen ? 'is-active' : ''">
                <svg x-show="!isFullscreen" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <svg x-show="isFullscreen" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>
            </button>
            <button type="button" class="pos-tool-btn" @click="toggleDark()" :title="darkMode ? 'Light' : 'Dark'">
                <svg x-show="!darkMode" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" style="display:none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
            @if(!empty($openSession))
                <a href="{{ route('counters.sessions.close-form', $openSession) }}" class="pos-close-day" title="Close day">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Close day
                </a>
            @endif
            <div class="status-badge" :class="isOnline ? 'online' : 'offline'"
                 @click="isOnline && pendingOfflineCount() > 0 && (syncPromptOpen = true)"
                 :title="pendingOfflineCount() > 0 ? (pendingOfflineCount() + ' offline bill(s) waiting') : ''"
                 :style="pendingOfflineCount() > 0 ? 'cursor:pointer' : ''">
                <span class="status-dot"></span>
                <span x-text="isOnline ? 'Online' : 'Offline'"></span>
                <span x-show="pendingOfflineCount() > 0" x-cloak
                      style="margin-left:4px;background:rgba(255,255,255,.2);padding:0 5px;border-radius:999px;font-size:10px;font-weight:800"
                      x-text="pendingOfflineCount()"></span>
            </div>
            <div class="pos-user">
                <div class="pos-user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="pos-user-meta">
                    <div class="pos-user-name">{{ Auth::user()->name }}</div>
                    @if(Auth::user()->isAdminUser())
                        <div class="pos-user-role" x-text="selectedCounterLabel()"></div>
                    @else
                        <div class="pos-user-role">{{ Auth::user()->counter->name ?? 'Counter' }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Counter fullscreen prompt (browser blocks auto-fullscreen without a click) --}}
    <div x-show="showFullscreenHint && !isFullscreen" x-cloak
         class="pos-fs-hint"
         x-transition.opacity>
        <div class="pos-fs-hint-inner">
            <div>
                <strong>Counter mode</strong>
                <span>Use the full screen so the register never shrinks into a small layout.</span>
            </div>
            <div class="pos-fs-hint-actions">
                <button type="button" class="pos-fs-btn" @click="enterCounterFullscreen()">Enter fullscreen</button>
                <button type="button" class="pos-fs-dismiss" @click="dismissFullscreenHint()">Not now</button>
            </div>
        </div>
    </div>

    <div class="pos-body">
        <aside class="pos-rail">
            <a href="{{ route('pos.index') }}" class="active" title="POS">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                POS
            </a>
            <a href="{{ route('dashboard') }}" title="Dashboard">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Home
            </a>
            @can('manage inventory')
            <a href="{{ route('products.index') }}" title="Products">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Products
            </a>
            @endcan
            @can('view sales ledger')
            <a href="{{ route('sales.index') }}" title="Orders">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Orders
            </a>
            <a href="{{ route('customers.index') }}" title="Customers">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Customers
            </a>
            @endcan
            <div class="pos-rail-foot">
                <div class="pos-rail-online"><span style="width:6px;height:6px;border-radius:50%;background:currentColor"></span> Online</div>
            </div>
        </aside>

        <div class="pos-root">
    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         LEFT PANEL - Products
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="panel-left">
        <div class="panel-top">
            <div class="filter-label">Category</div>
            <div class="cat-bar" @keydown.escape.window="openCatId = null">
                {{-- All products --}}
                <div class="cat-item">
                    <button type="button"
                            class="cat-btn"
                            :class="selectedCategory === 'all' ? 'active' : ''"
                            @click="pickAllProducts()">
                        All
                        <span class="cat-btn-count" x-text="products.length"></span>
                    </button>
                </div>

                {{-- Every category = its own brand dropdown --}}
                <template x-for="cat in categories" :key="'cat-btn-' + cat.id">
                    <div class="cat-item"
                         :class="openCatId == String(cat.id) ? 'open' : ''"
                         @mouseenter="openCatId = String(cat.id)"
                         @mouseleave="openCatId = null">
                        <button type="button"
                                class="cat-btn"
                                :class="selectedCategory == String(cat.id) ? 'active' : ''"
                                @click="toggleCatMenu(cat.id)">
                            <span x-text="cat.name"></span>
                            <span class="cat-btn-count" x-text="categoryProductCount(cat.id)"></span>
                            <svg class="cat-btn-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div class="cat-brand-menu" x-show="openCatId == String(cat.id)" x-cloak>
                            <div class="cat-brand-menu-title">Brands</div>
                            <button type="button" class="cat-brand-item"
                                    :class="selectedCategory == String(cat.id) && selectedBrand === 'all' ? 'active' : ''"
                                    @click="pickCategory(cat.id)">
                                <span>All brands</span>
                                <span class="cat-btn-count" x-text="categoryProductCount(cat.id)"></span>
                            </button>
                            <template x-for="brand in brandsForCategory(cat.id)" :key="'brand-' + cat.id + '-' + brand.id">
                                <button type="button" class="cat-brand-item"
                                        :class="selectedCategory == String(cat.id) && selectedBrand == String(brand.id) ? 'active' : ''"
                                        @click="pickBrand(cat.id, brand.id)">
                                    <span x-text="brand.name"></span>
                                    <span class="cat-btn-count" x-text="brandProductCount(cat.id, brand.id)"></span>
                                </button>
                            </template>
                            <button type="button" class="cat-brand-item"
                                    x-show="unbrandedCount(cat.id) > 0"
                                    :class="selectedCategory == String(cat.id) && selectedBrand === 'none' ? 'active' : ''"
                                    @click="pickBrand(cat.id, 'none')">
                                <span>No brand</span>
                                <span class="cat-btn-count" x-text="unbrandedCount(cat.id)"></span>
                            </button>
                            <div x-show="brandsForCategory(cat.id).length === 0 && unbrandedCount(cat.id) === 0"
                                 style="padding:8px 12px;font-size:12px;color:var(--text-3)">No products</div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Product grid with pictures (always visible) -->
        <div class="product-list">
            <template x-for="product in filteredProducts()" :key="product.id">
                <div @click="addToCart(product)"
                     class="product-card"
                     :class="{
                        'out-of-stock': product.stock_quantity < 1,
                        'in-cart': cartQty(product.id) > 0
                     }">

                    <div class="p-in-cart-tag" x-show="cartQty(product.id) > 0" x-cloak>In cart</div>
                    <div class="p-selected-badge"
                         x-show="cartQty(product.id) > 0"
                         x-text="cartQty(product.id)"
                         x-cloak></div>

                    <div class="p-img-wrap">
                        <template x-if="product.image">
                            <img :src="'/storage/' + product.image" :alt="product.name" loading="lazy">
                        </template>
                        <template x-if="!product.image">
                            <div class="p-avatar" x-text="productInitials(product.name)"></div>
                        </template>
                    </div>

                    <div class="p-body">
                        <div class="p-name" x-text="product.name" :title="product.name"></div>
                        <div class="p-meta">
                            <div class="p-brand-tag"
                                 x-show="product.brand_name || product.category_name"
                                 x-text="[product.brand_name, product.category_name].filter(Boolean).join(' · ')"
                                 x-cloak></div>
                            <div class="p-barcode" x-text="product.barcode || '—'"></div>
                        </div>
                        <div class="p-foot">
                            <div class="p-price">
                                <span class="p-price-sym">Tk</span><span x-text="formatNumber(product.selling_price)"></span>
                            </div>
                            <div class="stock-chip"
                                 :class="product.stock_quantity < 1 ? 'stock-out' : product.stock_quantity < 5 ? 'stock-low' : 'stock-ok'"
                                 x-text="product.stock_quantity < 1 ? 'Out of stock' : ('In Stock (' + product.stock_quantity + ')')">
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="filteredProducts().length === 0"
                 style="grid-column:1/-1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 16px;color:var(--text-3);text-align:center">
                <p style="font-size:14px;font-weight:650;color:var(--text-2)">No products found</p>
                <p style="font-size:12.5px;margin-top:4px">Try another category/brand, or clear search.</p>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         RIGHT PANEL - Cart
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="panel-right">

        <!-- Cart Header -->
        <div class="cart-head">
            <div class="cart-title">
                <div class="cart-badge-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                Cart
                <span class="cart-count" x-show="cartUnitCount() > 0" x-text="cartUnitCount()" x-cloak></span>
            </div>
            <div class="cart-head-actions">
                <button @click="holdCartsModalOpen = true"
                    x-show="heldCarts.length > 0"
                    x-cloak
                    class="held-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="heldCarts.length"></span> Held
                </button>
                <button @click="cart = []; lastAddedId = null" x-show="cart.length > 0" class="clear-btn">Clear Cart</button>
            </div>
        </div>

        <!-- Exchange Mode Banner -->
        <div x-show="isExchangeMode" x-cloak class="exchange-banner">
            <div style="display:flex;align-items:center;gap:7px">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Exchange Mode
            </div>
            <span>Credit: Tk<span x-text="formatNumber(exchangeCredit)"></span></span>
        </div>

        <div class="cart-items" id="pos-cart-items">
            <div class="cart-items-label" x-show="cart.length > 0" x-cloak>
                <span>Selected items</span>
                <strong x-text="cart.length + ' line · ' + cartUnitCount() + ' qty'"></strong>
            </div>
            <template x-for="(item, index) in cart" :key="item.id">
                <div class="cart-item" :class="{ 'at-limit': item.qty >= item.max_stock, 'just-added': lastAddedId === item.id }">
                    <div class="ci-thumb">
                        <template x-if="item.image">
                            <img :src="'/storage/' + item.image" :alt="item.name">
                        </template>
                        <template x-if="!item.image">
                            <span x-text="productInitials(item.name)"></span>
                        </template>
                    </div>
                    <div class="ci-info">
                        <div class="ci-name" x-text="item.name" :title="item.name"></div>
                        <div class="ci-unit">Tk<span x-text="formatNumber(item.price)"></span> each</div>
                    </div>
                    <div class="ci-sub">Tk<span x-text="formatNumber(item.price * item.qty)"></span></div>
                    <div class="qty-ctrl">
                        <button @click.stop="updateQty(index, -1)" class="qty-btn">-</button>
                        <div class="qty-num" x-text="item.qty"></div>
                        <button @click.stop="updateQty(index, 1)" class="qty-btn">+</button>
                    </div>
                    <button @click.stop="removeItem(index)" class="ci-remove" title="Remove">
                        <svg style="width:15px;height:15px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>

            <template x-if="cart.length === 0">
                <div class="cart-empty">
                    <div class="empty-ring">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="empty-title">Cart is empty</div>
                    <div class="empty-sub">Tap a product — it will appear here</div>
                </div>
            </template>
        </div>

        <div class="cart-extras">
            <div class="extras-body always-open">
                <div class="field-label">Customer (optional)</div>
                <div class="extras-grid">
                    <div class="cust-phone-wrap">
                        <input type="text" x-model="customerPhone" @input.debounce.500ms="searchCustomer()" placeholder="Mobile number" class="cust-input">
                        <div x-show="isSearchingCustomer" x-cloak style="position:absolute;right:8px;top:50%;transform:translateY(-50%)">
                            <svg class="animate-spin" style="width:13px;height:13px;color:var(--teal)" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                        </div>
                    </div>
                    <input type="text" x-model="customerName" placeholder="Customer name" class="cust-input">
                </div>

                <div class="field-label" style="margin-top:2px">Discount</div>
                <div class="discount-row">
                    <div class="type-toggle">
                        <button type="button" @click="discountType = 'percent'" class="tt-btn" :class="discountType === 'percent' ? 'active' : ''">%</button>
                        <button type="button" @click="discountType = 'flat'" class="tt-btn" :class="discountType === 'flat' ? 'active' : ''">Tk</button>
                    </div>
                    <input type="number" x-model.number="discountValue" class="discount-input" :placeholder="discountType === 'percent' ? '%' : 'Amount'" min="0" :max="discountType === 'percent' ? 100 : getTotal()">
                    <span x-show="getDiscount() > 0" class="discount-badge" x-cloak>
                        -Tk<span x-text="formatNumber(getDiscount())"></span>
                    </span>
                </div>
                <div class="coupon-row">
                    <input type="text" x-model="couponCode" @keyup.enter="applyCoupon()" placeholder="Coupon code" class="coupon-input">
                    <button type="button" @click="applyCoupon()" class="coupon-apply-btn">Apply</button>
                </div>
                <div x-show="appliedCoupon" x-cloak class="coupon-applied">
                    <span>Coupon <strong x-text="appliedCoupon?.code"></strong> applied</span>
                    <button type="button" @click="removeCoupon()" class="coupon-remove">Remove</button>
                </div>
            </div>
        </div>
        <!-- Cart Footer -->
        <div class="cart-foot">
            <div class="summary-rows">
                <div class="sum-row">
                    <span class="sum-label">Subtotal</span>
                    <span class="sum-val">Tk<span x-text="formatNumber(getTotal())"></span></span>
                </div>
                <div class="sum-row discount" x-show="getDiscount() > 0">
                    <span class="sum-label">Discount</span>
                    <span class="sum-val">-Tk<span x-text="formatNumber(getDiscount())"></span></span>
                </div>
                <div class="sum-row exchange" x-show="isExchangeMode && exchangeCredit > 0" style="display:none">
                    <span class="sum-label">Exchange Credit</span>
                    <span class="sum-val">-Tk<span x-text="formatNumber(exchangeCredit)"></span></span>
                </div>
                <div class="sum-row">
                    <span class="sum-label">Tax</span>
                    <span class="sum-val" style="color:var(--text-3)">Included</span>
                </div>
            </div>

            <div class="total-row">
                <div class="total-label">Total</div>
                <div class="total-val">
                    <span class="total-sym">Tk</span><span x-text="formatNumber(getPayableTotal())"></span>
                </div>
            </div>

            <div class="pay-methods">
                <button type="button" class="pay-method primary" @click="openCheckout()" :disabled="!canProceedToCheckout()">Cash</button>
                <button type="button" class="pay-method" @click="openCheckout()" :disabled="!canProceedToCheckout()">Card</button>
                <button type="button" class="pay-method" @click="openCheckout()" :disabled="!canProceedToCheckout()">Other</button>
            </div>

            <div class="cart-actions">
                <button @click="suspendCurrentCart()" :disabled="cart.length === 0" class="hold-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Hold
                </button>
                <button @click="openCheckout()" :disabled="!canProceedToCheckout()" class="pay-btn">
                    <span style="display:inline-flex;align-items:center;gap:6px">
                        Pay Tk<span x-text="formatNumber(getPayableTotal())"></span>
                    </span>
                    <span class="pay-hint">F2</span>
                </button>
            </div>
        </div>
    </div>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div x-show="checkoutModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

        <div class="modal-bg" @click="checkoutModalOpen = false"></div>

        <div class="modal"
             x-show="checkoutModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">

            <div class="modal-head">
                <div>
                    <div class="modal-title">Complete Payment</div>
                    <div class="modal-subtitle" x-text="cartUnitCount() + ' item(s) · ' + (customerName || 'Walk-in Customer')"></div>
                </div>
                <button type="button" @click="checkoutModalOpen = false" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                @if(Auth::user()->isAdminUser())
                    <div class="mb-3" style="background:rgba(37,99,235,.08);border:1px solid rgba(37,99,235,.2);border-radius:12px;padding:12px">
                        <label style="display:block;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--slate);margin-bottom:6px">Your open till</label>
                        <template x-for="c in posCounters" :key="c.id">
                            <div style="font-weight:800;color:var(--ink)" x-text="c.name + ' · opened by you'"></div>
                        </template>
                        <p style="margin:8px 0 0;font-size:11px;color:var(--slate)">
                            Admin sells only on a free counter after entering opening cash. Busy tills stay with their cashier.
                        </p>
                    </div>
                @endif
                <div class="amount-display">
                    <div class="amount-label">Bill total</div>
                    <div class="amount-value">
                        <span class="ccy">Tk</span><span x-text="formatNumber(getPayableTotal())"></span>
                    </div>
                    <div x-show="getDiscount() > 0" class="amount-note" style="color:var(--green)" x-cloak>
                         Discount Tk<span x-text="formatNumber(getDiscount())"></span>
                    </div>
                    <div x-show="isExchangeMode" class="amount-note" style="color:var(--amber)" x-cloak>
                        Exchange credit: -Tk<span x-text="formatNumber(exchangeCredit)"></span>
                    </div>
                    <div class="amount-note" style="color:var(--slate);margin-top:4px">
                        Enter what the customer pays — any shortfall is counted as Less.
                    </div>
                </div>

                <div x-show="hasLowStockItems()" class="warning-box" x-cloak>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="warning-text">One or more items are at stock limit. Verify before confirming.</div>
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                        <label class="field-label" style="margin-bottom:0">Customer pays</label>
                        <span style="font-size:9px;font-weight:800;color:var(--teal);background:var(--teal-bg);padding:2px 7px;border-radius:100px;border:1px solid var(--teal-border)">Split pay</span>
                    </div>
                    <div class="split-grid">
                        <div class="sp-row">
                            <div class="sp-label">Cash</div>
                            <input type="number" x-model.number="payCash" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                        <div class="sp-row">
                            <div class="sp-label">Card</div>
                            <input type="number" x-model.number="payCard" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                        <div class="sp-row">
                            <div class="sp-label">bKash</div>
                            <input type="number" x-model.number="payBkash" class="sp-input" placeholder="0" min="0" step="0.01"
                                   @keydown.enter.prevent="if (canConfirmSale() && !isProcessing) submitOrder()">
                        </div>
                    </div>
                    <div class="qc-grid">
                        <button type="button" @click="payCash = getPayableTotal(); payCard = 0; payBkash = 0;" class="qc-btn exact">Exact</button>
                        <button type="button" @click="payCash = 500"  class="qc-btn">Tk500</button>
                        <button type="button" @click="payCash = 1000" class="qc-btn">Tk1K</button>
                        <button type="button" @click="payCash = 2000" class="qc-btn">Tk2K</button>
                    </div>
                </div>

                {{-- Paid / less / change summary --}}
                <div class="pay-summary">
                    <div class="pay-summary-cell due">
                        <div class="ps-label">Bill total</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPayableTotal())"></span></div>
                    </div>
                    <div class="pay-summary-cell paid">
                        <div class="ps-label">Customer pays</div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getPaidAmount())"></span></div>
                    </div>
                    <div class="pay-summary-cell change"
                         :class="getLessAmount() > 0 ? 'less' : (getChange() >= 0 ? 'ok' : 'err')">
                        <div class="ps-label" x-text="getLessAmount() > 0 ? 'Less (discount)' : (getChange() >= 0 ? 'Change to return' : 'Still owed')"></div>
                        <div class="ps-val">Tk<span x-text="formatNumber(getLessAmount() > 0 ? getLessAmount() : Math.abs(getChange()))"></span></div>
                    </div>
                </div>
                <p x-show="getLessAmount() > 0" class="err-hint" style="color:var(--green);border-color:var(--green)" x-cloak>
                    Rest of bill (Tk<span x-text="formatNumber(getLessAmount())"></span>) will be counted as Less / discount.
                </p>
            </div>

            <div class="modal-foot">
                <button type="button" @click="checkoutModalOpen = false" class="modal-cancel">
                    Cancel <span class="modal-kbd">ESC</span>
                </button>
                <button type="button" @click="submitOrder()"
                        :disabled="!canConfirmSale() || isProcessing"
                        class="modal-confirm"
                        x-ref="confirmSaleBtn">
                    <svg x-show="!isProcessing" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-show="!isProcessing" x-cloak style="display:flex;align-items:center;gap:6px">
                        Confirm sale <span class="modal-kbd">ENTER</span>
                    </span>
                    <span x-show="isProcessing" x-cloak>Processing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- INVOICE PREVIEW MODAL (after successful sale) -->
    <div x-show="invoiceModalOpen" style="display:none" class="modal-overlay invoice-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="closeInvoiceModal()"></div>
        <div class="modal invoice-modal"
             x-show="invoiceModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Sale complete · Invoice</div>
                    <div class="modal-subtitle" x-text="(lastSale?.invoice_no || '') + (lastSale?.customer ? ' · ' + lastSale.customer : '')"></div>
                </div>
                <button type="button" @click="closeInvoiceModal()" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="invoice-meta" x-show="lastSale" x-cloak>
                <div class="invoice-meta-item">
                    <div class="im-label">Customer paid</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.paid_amount || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="(lastSale?.discount_amount || 0) > 0 && (lastSale?.change || 0) <= 0" x-cloak>
                    <div class="im-label">Less / discount</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.discount_amount || 0)"></span></div>
                </div>
                <div class="invoice-meta-item change" x-show="(lastSale?.change || 0) > 0 || ((lastSale?.discount_amount || 0) <= 0)" x-cloak>
                    <div class="im-label">Change to return</div>
                    <div class="im-val">Tk<span x-text="formatNumber(lastSale?.change || 0)"></span></div>
                </div>
            </div>
            <div class="invoice-frame-wrap">
                <iframe x-ref="receiptFrame" class="invoice-frame"
                        :src="lastSale?.receipt_html ? false : (lastSale?.receipt_url || 'about:blank')"
                        :srcdoc="lastSale?.receipt_html || false"
                        title="POS Invoice"></iframe>
            </div>
            <div class="modal-foot">
                <button type="button" @click="closeInvoiceModal()" class="modal-cancel">
                    Close <span class="modal-kbd">ESC</span>
                </button>
                <button type="button" @click="printLastReceipt()" class="modal-confirm" style="background:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.25)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print invoice <span class="modal-kbd">ENTER</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SYNC PERMISSION (when network returns) -->
    <div x-show="syncPromptOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="syncPromptOpen = false"></div>
        <div class="modal" style="max-width:420px"
             x-show="syncPromptOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="modal-head">
                <div>
                    <div class="modal-title">Network is back</div>
                    <div class="modal-subtitle">Offline bills are ready to upload</div>
                </div>
                <button type="button" @click="syncPromptOpen = false" class="modal-close" aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p style="font-size:13.5px;line-height:1.5;color:var(--text-2);margin:0">
                    <strong x-text="pendingOfflineCount()"></strong> offline bill(s) were saved while the network was down.
                    Sync now to update stock and sales on the server?
                </p>
                <p style="font-size:12px;color:var(--text-3);margin:10px 0 0">You can also sync later by clicking the Online badge.</p>
            </div>
            <div class="modal-foot">
                <button type="button" @click="syncPromptOpen = false" class="modal-cancel">Later</button>
                <button type="button" @click="confirmSyncOffline()" class="modal-confirm" :disabled="isSyncing"
                        style="background:var(--blue);box-shadow:0 4px 12px rgba(37,99,235,.25)">
                    <span x-show="!isSyncing" x-cloak>Sync now</span>
                    <span x-show="isSyncing" x-cloak>Syncing...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         HELD CARTS MODAL
    ═══════════════════════════════════════════════════════════════ -->
    <div x-show="holdCartsModalOpen" style="display:none" class="modal-overlay"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="modal-bg" @click="holdCartsModalOpen = false"></div>
        <div class="modal" style="max-width:440px"
             x-show="holdCartsModalOpen"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            <div class="modal-head">
                <div>
                    <div class="modal-title" style="display:flex;align-items:center;gap:8px">
                        <svg style="width:18px;height:18px;color:var(--amber)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Suspended Carts
                    </div>
                    <div class="modal-subtitle">Tap Resume to continue a suspended order</div>
                </div>
                <button @click="holdCartsModalOpen = false" class="modal-close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div style="max-height:400px;overflow-y:auto;background:var(--bg)">
                <template x-if="heldCarts.length === 0">
                    <div style="padding:36px;text-align:center;font-size:13px;font-weight:600;color:var(--text-3)">No suspended carts.</div>
                </template>
                <template x-for="(hCart, index) in heldCarts" :key="hCart.id">
                    <div style="padding:13px 18px;display:flex;justify-content:space-between;align-items:center;background:var(--surface);border-bottom:1px solid var(--border)">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--text-1)">
                                Held at <span style="color:var(--teal);font-family:var(--mono)" x-text="hCart.time"></span>
                            </div>
                            <div style="font-size:11px;color:var(--text-2);margin-top:3px;font-family:var(--mono)">
                                <span x-text="hCart.cartData.length"></span> items  ·  Tk<span x-text="formatNumber(hCart.total)"></span>
                            </div>
                            <div x-show="hCart.customerName || hCart.customerPhone"
                                 style="font-size:11px;color:var(--text-3);margin-top:2px">
                                <span x-text="hCart.customerName || 'Guest'"></span>
                                <span x-show="hCart.customerPhone" x-text="'  ·  ' + hCart.customerPhone"></span>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px">
                            <button @click="resumeHeldCart(index)"
                                style="background:var(--teal-bg);color:var(--teal);border:1.5px solid var(--teal-border);padding:7px 16px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:var(--font)">
                                Resume
                            </button>
                            <button @click="deleteHeldCart(index)"
                                style="background:var(--red-bg);color:var(--red);border:1.5px solid var(--red-border);padding:7px 16px;border-radius:8px;font-size:12px;font-weight:800;cursor:pointer;font-family:var(--font)">
                                Drop
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         KEYBOARD SHORTCUT CHEATSHEET
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div x-show="kbOpen" style="display:none" class="kb-overlay"
         @click.self="kbOpen = false"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="kb-modal"
             x-transition:enter="ease-out duration-250" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="kb-head">
                <h2 class="kb-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Keyboard Shortcuts
                </h2>
                <button @click="kbOpen = false" class="modal-close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="kb-grid">
                <div class="kb-row">
                    <span class="kb-desc">Pay</span>
                    <span class="key">F2</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Confirm Sale (when modal open)</span>
                    <span class="key">Enter</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Close modal / Clear search</span>
                    <span class="key">Esc</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Auto-add single barcode result</span>
                    <span class="key">Enter</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Show this cheatsheet</span>
                    <span class="key">?</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Toggle dark / light mode</span>
                    <span class="key">D</span>
                </div>
                <div class="kb-row">
                    <span class="kb-desc">Toggle fullscreen</span>
                    <span class="key">F11</span>
                    <span class="key">F</span>
                </div>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
         TOAST NOTIFICATIONS
    â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="toast-dock">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="toast" :class="toast.type">
                <div class="toast-bar"></div>
                <span class="toast-msg" x-text="toast.msg"></span>
            </div>
        </template>
    </div>

</div>

<script>
function posSystem() {
    return {
        /* â”€â”€ Core â”€â”€ */
        isOnline: navigator.onLine,
        showExtras: false,
        isSyncing: false,
        syncPromptOpen: false,
        darkMode: localStorage.getItem('nexa_dark') === 'true',
        isFullscreen: !!(document.fullscreenElement || document.webkitFullscreenElement),
        showFullscreenHint: localStorage.getItem('nexa_pos_fs_hint') !== 'dismissed',
        shopName: @json(Auth::user()->shop->name ?? 'Nexa POS'),
        cashierName: @json(Auth::user()->name),
        offlinePendingTick: 0, // forces UI refresh of pending count

        search: '',
        selectedCategory: 'all',
        selectedBrand: 'all',
        openCatId: null,
        categories: @json($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
        brands: @json($brands),
        products: @json($products),
        cart: [],
        lastAddedId: null,

        /* â”€â”€ Customer â”€â”€ */
        customerName: '',
        customerPhone: '',
        isSearchingCustomer: false,

        /* â”€â”€ Discount â”€â”€ */
        discountType: 'percent',
        discountValue: 0,
        couponCode: '',
        appliedCoupon: null,

        /* â”€â”€ Checkout â”€â”€ */
        checkoutModalOpen: false,
        invoiceModalOpen: false,
        lastSale: null,
        isProcessing: false,
        payCash: 0,
        payCard: 0,
        payBkash: 0,
        isAdminPos: {{ Auth::user()->isAdminUser() ? 'true' : 'false' }},
        posCounters: @json($posCounters ?? []),
        selectedCounterId: '{{ $defaultPosCounterId ?? '' }}',

        /* â”€â”€ UI State â”€â”€ */
        heldCarts: JSON.parse(localStorage.getItem('nexa_held_carts')) || [],
        holdCartsModalOpen: false,
        kbOpen: false,
        toasts: [],

        /* â”€â”€ Exchange Mode â”€â”€ */
        isExchangeMode: {{ $exchangeOrder ? 'true' : 'false' }},
        exchangeOrderId: {{ $exchangeOrder ?? 'null' }},
        returnProductId: {{ $returnProduct ?? 'null' }},
        returnQty: {{ $returnQty ?? 0 }},
        exchangeCredit: {{ $credit ?? 0 }},

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           INIT
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        init() {
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.showToast('Network is back', 'success');
                if (this.pendingOfflineCount() > 0) {
                    this.syncPromptOpen = true;
                }
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.showToast('Offline mode — you can still sell and print bills', 'warning');
            });
            // Ask permission if offline bills already waiting when POS opens online
            if (this.isOnline && this.pendingOfflineCount() > 0) {
                this.$nextTick(() => { this.syncPromptOpen = true; });
            }
            const syncFs = () => {
                this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
                if (this.isFullscreen) this.showFullscreenHint = false;
            };
            document.addEventListener('fullscreenchange', syncFs);
            document.addEventListener('webkitfullscreenchange', syncFs);
            syncFs();

            // Expand popup/window to fill the monitor when launched for a till
            try {
                if (window.name === 'nexa_pos_terminal' || window.opener) {
                    window.moveTo(0, 0);
                    window.resizeTo(screen.availWidth, screen.availHeight);
                }
            } catch (e) {}
        },

        /* ───────────────────────────────
           DARK MODE
        ─────────────────────────────── */
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('nexa_dark', this.darkMode);
        },

        /* ───────────────────────────────
           FULLSCREEN
        ─────────────────────────────── */
        async toggleFullscreen() {
            try {
                const root = document.documentElement;
                const active = document.fullscreenElement || document.webkitFullscreenElement;
                if (active) {
                    if (document.exitFullscreen) await document.exitFullscreen();
                    else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                } else {
                    if (root.requestFullscreen) await root.requestFullscreen();
                    else if (root.webkitRequestFullscreen) root.webkitRequestFullscreen();
                    else {
                        this.showToast('Fullscreen not supported in this browser', 'warning');
                        return;
                    }
                }
            } catch (e) {
                this.showToast('Could not toggle fullscreen', 'error');
            }
            this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (this.isFullscreen) this.showFullscreenHint = false;
        },

        async enterCounterFullscreen() {
            await this.toggleFullscreen();
            if (this.isFullscreen) {
                localStorage.setItem('nexa_pos_fs_hint', 'dismissed');
                this.showFullscreenHint = false;
            }
        },

        dismissFullscreenHint() {
            localStorage.setItem('nexa_pos_fs_hint', 'dismissed');
            this.showFullscreenHint = false;
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           TOAST SYSTEM
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        showToast(msg, type = 'info', ms = 3200) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, msg, type });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, ms);
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           BEEP FEEDBACK
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        playBeep(ok = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = ok ? 1200 : 380;
                osc.type = ok ? 'sine' : 'square';
                gain.gain.setValueAtTime(0.22, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (ok ? 0.13 : 0.22));
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + (ok ? 0.13 : 0.22));
            } catch(e) {}
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           KEYBOARD SHORTCUTS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        handleKeydown(e) {
            // Invoice preview after sale — Enter prints, Esc closes
            if (this.invoiceModalOpen) {
                if (e.key === 'Enter' || e.key === 'NumpadEnter') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.printLastReceipt();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.closeInvoiceModal();
                }
                return;
            }
            // Inside checkout modal — Enter confirms, Esc cancels
            if (this.checkoutModalOpen) {
                if (e.key === 'Enter' || e.key === 'NumpadEnter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (this.canConfirmSale() && !this.isProcessing) this.submitOrder();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.checkoutModalOpen = false;
                }
                return;
            }
            // Shortcut overlay open
            if (this.kbOpen) {
                if (e.key === 'Escape') { this.kbOpen = false; }
                return;
            }
            // Global
            if (e.key === 'F2')    { e.preventDefault(); if (this.canProceedToCheckout()) this.openCheckout(); }
            if (e.key === 'F11')   { e.preventDefault(); this.toggleFullscreen(); }
            if (e.key === 'Escape'){ this.search = ''; this.$refs.searchInput.focus(); }
            if (e.key === '?')     { e.preventDefault(); this.kbOpen = true; }
            if ((e.key === 'd' || e.key === 'D') && document.activeElement.tagName !== 'INPUT') this.toggleDark();
            if ((e.key === 'f' || e.key === 'F') && !e.ctrlKey && !e.metaKey && !e.altKey && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                this.toggleFullscreen();
            }
            // Enter on search is handled by @keydown.enter on the input
        },

        /* ───────────────────────────────
           EACH CATEGORY HAS BRAND DROPDOWN
        ─────────────────────────────── */
        toggleCatMenu(id) {
            const key = String(id);
            this.openCatId = this.openCatId === key ? null : key;
        },
        pickAllProducts() {
            this.selectedCategory = 'all';
            this.selectedBrand = 'all';
            this.openCatId = null;
        },
        pickCategory(id) {
            this.selectedCategory = String(id);
            this.selectedBrand = 'all';
            this.openCatId = null;
        },
        pickBrand(categoryId, brandId) {
            this.selectedCategory = String(categoryId);
            this.selectedBrand = String(brandId);
            this.openCatId = null;
        },
        productsInCategory(categoryId) {
            return this.products.filter(p => String(p.category_id) === String(categoryId));
        },
        categoryProductCount(categoryId) {
            return this.productsInCategory(categoryId).length;
        },
        brandsForCategory(categoryId) {
            const ids = new Set();
            const looseNames = new Map();
            this.productsInCategory(categoryId).forEach(p => {
                const resolved = this.resolveBrand(p);
                if (resolved.id) {
                    ids.add(String(resolved.id));
                } else if (resolved.name) {
                    const key = resolved.name.toLowerCase();
                    if (!looseNames.has(key)) looseNames.set(key, resolved.name);
                }
            });
            const fromCatalog = (this.brands || []).filter(b => ids.has(String(b.id)));
            const synthetic = Array.from(looseNames.entries()).map(([key, name], i) => ({
                id: 'name:' + key,
                name,
                _synthetic: true,
            }));
            return [...fromCatalog, ...synthetic];
        },
        resolveBrand(product) {
            if (product.brand_id) {
                return { id: String(product.brand_id), name: product.brand_name || null };
            }
            const explicit = String(product.brand_name || '').trim();
            if (explicit) {
                const match = (this.brands || []).find(b => String(b.name).toLowerCase() === explicit.toLowerCase());
                if (match) return { id: String(match.id), name: match.name };
                return { id: null, name: explicit };
            }
            const pname = String(product.name || '').trim().toLowerCase();
            if (pname) {
                const match = (this.brands || [])
                    .slice()
                    .sort((a, b) => String(b.name).length - String(a.name).length)
                    .find(b => {
                        const bn = String(b.name || '').trim().toLowerCase();
                        if (!bn) return false;
                        return pname === bn || pname.startsWith(bn + ' ') || pname.startsWith(bn + '-') || pname.startsWith(bn + '_');
                    });
                if (match) return { id: String(match.id), name: match.name };
            }
            return { id: null, name: null };
        },
        brandProductCount(categoryId, brandId) {
            const key = String(brandId);
            return this.productsInCategory(categoryId).filter(p => {
                const resolved = this.resolveBrand(p);
                if (key.startsWith('name:')) {
                    return !resolved.id && resolved.name && ('name:' + resolved.name.toLowerCase()) === key;
                }
                return resolved.id && String(resolved.id) === key;
            }).length;
        },
        unbrandedCount(categoryId) {
            return this.productsInCategory(categoryId).filter(p => {
                const resolved = this.resolveBrand(p);
                return !resolved.id && !resolved.name;
            }).length;
        },
        matchesBrand(product) {
            if (this.selectedBrand === 'all') return true;
            const resolved = this.resolveBrand(product);
            if (this.selectedBrand === 'none') return !resolved.id && !resolved.name;
            const key = String(this.selectedBrand);
            if (key.startsWith('name:')) {
                return !resolved.id && resolved.name && ('name:' + resolved.name.toLowerCase()) === key;
            }
            return resolved.id && String(resolved.id) === key;
        },
        matchesCategory(product) {
            return this.selectedCategory === 'all' || String(product.category_id) === String(this.selectedCategory);
        },
        filteredProducts() {
            const q = (this.search || '').trim().toLowerCase();
            return this.products.filter(p => {
                if (!this.matchesCategory(p) || !this.matchesBrand(p)) return false;
                if (!q) return true;
                const name = (p.name || '').toLowerCase();
                const barcode = String(p.barcode || '').toLowerCase();
                const sku = String(p.sku || '').toLowerCase();
                const brand = String(p.brand_name || '').toLowerCase();
                const category = String(p.category_name || '').toLowerCase();
                return name.includes(q) || barcode.includes(q) || sku.includes(q) || brand.includes(q) || category.includes(q);
            });
        },

        findExactScanMatch(code) {
            const q = String(code || '').trim().toLowerCase();
            if (!q) return null;
            return this.products.find(p => {
                const barcode = String(p.barcode || '').trim().toLowerCase();
                const sku = String(p.sku || '').trim().toLowerCase();
                return (barcode && barcode === q) || (sku && sku === q);
            }) || null;
        },

        onSearchInput() {
            if (this.checkoutModalOpen || this.invoiceModalOpen) return;
            const q = (this.search || '').trim();
            // Barcode scanners type fast; auto-add on exact barcode/SKU match (no click needed)
            if (q.length < 3) return;
            const match = this.findExactScanMatch(q);
            if (match) {
                this.addToCart(match);
            }
        },

        onSearchEnter() {
            if (this.checkoutModalOpen || this.invoiceModalOpen) return;
            const q = (this.search || '').trim();
            if (!q) return;

            // Prefer exact barcode / SKU
            const exact = this.findExactScanMatch(q);
            if (exact) {
                this.addToCart(exact);
                return;
            }

            // Otherwise add if only one filtered result
            const filtered = this.filteredProducts();
            if (filtered.length === 1) {
                this.addToCart(filtered[0]);
                return;
            }

            if (filtered.length === 0) {
                this.playBeep(false);
                this.showToast('No product found for “' + q + '”', 'error');
                this.search = '';
                this.$nextTick(() => this.$refs.searchInput?.focus());
            }
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CUSTOMER LOOKUP
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        async searchCustomer() {
            if (this.customerPhone.length < 9) {
                if (!this.customerPhone) this.customerName = '';
                return;
            }
            if (!this.isOnline) return;
            this.isSearchingCustomer = true;
            try {
                const res = await fetch(`{{ route('pos.customer-lookup') }}?phone=${this.customerPhone}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.found) {
                        this.customerName = data.name;
                        this.showToast('Customer found: ' + data.name, 'success');
                    }
                }
            } catch(e) {}
            this.isSearchingCustomer = false;
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CART
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        addToCart(product) {
            if (product.stock_quantity < 1) {
                this.playBeep(false);
                this.showToast(product.name + ' is out of stock!', 'error');
                return;
            }
            const existing = this.cart.find(i => i.id === product.id);
            if (existing) {
                if (existing.qty < product.stock_quantity) {
                    existing.qty++;
                    this.lastAddedId = product.id;
                    this.playBeep(true);
                } else {
                    this.playBeep(false);
                    this.showToast('Maximum stock reached for ' + product.name, 'warning');
                }
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: product.selling_price,
                    qty: 1,
                    max_stock: product.stock_quantity,
                    image: product.image || null,
                });
                this.lastAddedId = product.id;
                this.playBeep(true);
            }
            this.search = '';
            this.$refs.searchInput.focus();
            this.$nextTick(() => {
                const box = document.getElementById('pos-cart-items');
                if (box) box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        },

        cartQty(productId) {
            const item = this.cart.find(i => i.id === productId);
            return item ? item.qty : 0;
        },

        cartUnitCount() {
            return this.cart.reduce((s, i) => s + i.qty, 0);
        },

        productInitials(name) {
            if (!name) return '?';
            const parts = String(name).trim().split(/\s+/).filter(Boolean);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[1][0]).toUpperCase();
        },

        updateQty(index, amount) {
            const item   = this.cart[index];
            const newQty = item.qty + amount;
            if (newQty <= 0) {
                if (this.lastAddedId === item.id) this.lastAddedId = null;
                this.cart.splice(index, 1);
            } else if (newQty <= item.max_stock) {
                item.qty = newQty;
                this.lastAddedId = item.id;
            } else {
                this.playBeep(false);
                this.showToast('Cannot exceed available stock for ' + item.name, 'warning');
            }
        },

        removeItem(index) {
            const removed = this.cart[index];
            this.cart.splice(index, 1);
            if (removed && this.lastAddedId === removed.id) this.lastAddedId = null;
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           DISCOUNT & COUPONS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        getDiscount() {
            let disc = 0;
            // Coupon takes priority over manual discount
            if (this.appliedCoupon) {
                disc = this.appliedCoupon.type === 'percent'
                    ? (this.getTotal() * this.appliedCoupon.value / 100)
                    : this.appliedCoupon.value;
            } else if (this.discountValue > 0) {
                disc = this.discountType === 'percent'
                    ? (this.getTotal() * Math.min(this.discountValue, 100) / 100)
                    : this.discountValue;
            }
            return Math.max(0, Math.min(disc, this.getTotal()));
        },

        applyCoupon() {
            const code = this.couponCode.trim().toUpperCase();
            if (!code) return;
            // Demo coupons - validate server-side in production
            const coupons = {
                'SAVE10':  { code: 'SAVE10',  type: 'percent', value: 10 },
                'FLAT50':  { code: 'FLAT50',  type: 'flat',    value: 50 },
                'WELCOME': { code: 'WELCOME', type: 'percent', value: 5  }
            };
            if (coupons[code]) {
                this.appliedCoupon = coupons[code];
                this.discountValue = 0;
                this.showToast('Coupon applied! ' + (coupons[code].type === 'percent' ? coupons[code].value + '% off' : 'Tk' + coupons[code].value + ' off'), 'success');
            } else {
                this.playBeep(false);
                this.showToast('Invalid coupon code: ' + code, 'error');
            }
        },

        removeCoupon() {
            this.appliedCoupon = null;
            this.couponCode    = '';
            this.showToast('Coupon removed', 'info');
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           TOTALS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        getTotal() {
            return this.cart.reduce((s, i) => s + (i.price * i.qty), 0);
        },
        getPayableTotal() {
            return Math.max(0, this.getTotal() - this.getDiscount() - this.exchangeCredit);
        },
        getPaidAmount() {
            return (Number(this.payCash) || 0) + (Number(this.payCard) || 0) + (Number(this.payBkash) || 0);
        },
        /** Shortfall when customer pays less than bill — counted as Less / discount. */
        getLessAmount() {
            return Math.max(0, this.getPayableTotal() - this.getPaidAmount());
        },
        /** Cart discount + pay-less amount. */
        getTotalDiscountAmount() {
            return this.getDiscount() + this.getLessAmount();
        },
        getChange() {
            return Math.max(0, this.getPaidAmount() - this.getPayableTotal());
        },
        canConfirmSale() {
            if (this.cart.length === 0) return false;
            // Fully covered by exchange credit — nothing to collect
            if (this.getPayableTotal() <= 0) return true;
            // Must enter what the customer pays (shortfall becomes Less)
            return this.getPaidAmount() > 0;
        },
        canProceedToCheckout() {
            if (this.cart.length === 0) return false;
            if (this.isExchangeMode && this.getTotal() < this.exchangeCredit) return false;
            return true;
        },
        hasLowStockItems() {
            return this.cart.some(i => i.qty >= i.max_stock);
        },
        formatNumber(n) {
            return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        getPaymentMethodString() {
            const methods = [];
            if (this.payCash   > 0) methods.push('Cash');
            if (this.payCard   > 0) methods.push('Card');
            if (this.payBkash  > 0) methods.push('bKash');
            if (methods.length > 1) return methods.join(' + ');
            if (methods.length === 1) return methods[0].toLowerCase();
            return 'cash';
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           HOLD / RESUME CARTS
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        suspendCurrentCart() {
            if (!this.cart.length) return;
            const rec = {
                id:           Date.now(),
                time:         new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                cartData:     JSON.parse(JSON.stringify(this.cart)),
                customerName: this.customerName,
                customerPhone:this.customerPhone,
                total:        this.getTotal()
            };
            this.heldCarts.push(rec);
            localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
            this.cart = [];
            this.customerName = '';
            this.customerPhone = '';
            this.showToast('Cart suspended. Ready for next customer.', 'info');
        },
        resumeHeldCart(index) {
            const rec = this.heldCarts[index];
            if (this.cart.length > 0 && !confirm('Resume this cart? Your current cart will be cleared.')) return;
            this.cart          = JSON.parse(JSON.stringify(rec.cartData));
            this.customerName  = rec.customerName  || '';
            this.customerPhone = rec.customerPhone || '';
            this.heldCarts.splice(index, 1);
            localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
            this.holdCartsModalOpen = false;
            this.showToast('Cart resumed!', 'success');
        },
        deleteHeldCart(index) {
            if (confirm('Permanently delete this suspended cart?')) {
                this.heldCarts.splice(index, 1);
                localStorage.setItem('nexa_held_carts', JSON.stringify(this.heldCarts));
            }
        },

        /* â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
           CHECKOUT
        â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
openCheckout() {
            if (!this.canProceedToCheckout()) {
                this.showToast('Cart must total Tk' + this.formatNumber(this.exchangeCredit) + ' or more for exchange.', 'error');
                return;
            }
            if (this.hasLowStockItems()) this.showToast('! Some items are at stock limit - check before confirming', 'warning');
            // Leave cash empty so cashier types what the customer pays; Exact fills bill total
            this.payCash  = '';
            this.payCard  = 0;
            this.payBkash = 0;
            this.checkoutModalOpen = true;
            this.$nextTick(() => {
                const cash = document.querySelector('.modal .sp-input');
                if (cash) { cash.focus(); cash.select(); }
            });
        },

        async submitOrder() {
            if (this.isProcessing || !this.canConfirmSale()) return;
            this.isProcessing = true;

            const payload = {
                client_uuid:             (window.crypto && crypto.randomUUID)
                    ? crypto.randomUUID()
                    : ('off-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10)),
                cart:                    this.cart,
                items:                   this.cart,
                total_amount:            this.getTotal(),
                discount_amount:         this.getTotalDiscountAmount(),
                coupon_code:             this.appliedCoupon?.code || null,
                payment_method:          this.getPaymentMethodString(),
                paid_amount:             this.getPaidAmount(),
                cash_paid:               Number(this.payCash) || 0,
                card_paid:               Number(this.payCard) || 0,
                mobile_paid:             Number(this.payBkash) || 0,
                customer_name:           this.customerName,
                customer_phone:          this.customerPhone,
                created_at:              new Date().toISOString(),
                is_exchange:             this.isExchangeMode,
                exchange_for_order_id:   this.exchangeOrderId,
                return_product_id:       this.returnProductId,
                return_qty:              this.returnQty,
                exchange_credit:         this.exchangeCredit,
                counter_id:              this.isAdminPos ? (Number(this.selectedCounterId) || null) : null,
            };

            if (this.isAdminPos && !this.hasOpenAdminCounter()) {
                this.isProcessing = false;
                this.playBeep(false);
                this.showToast('Open a cash session on a counter, then select it before billing.', 'error');
                return;
            }

            if (this.isOnline) {
                try {
                    const res  = await fetch('{{ route('pos.checkout') }}', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body:    JSON.stringify(payload)
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        this.playBeep(true);
                        const paidSnap = this.getPaidAmount();
                        const lessSnap = this.getLessAmount();
                        const changeSnap = data.change ?? Math.max(0, paidSnap - this.getPayableTotal());
                        const customerSnap = this.customerName || 'Walk-in Customer';

                        this.cart = []; this.customerName = ''; this.customerPhone = '';
                        this.discountValue = 0; this.appliedCoupon = null; this.couponCode = '';
                        this.lastAddedId = null;
                        this.checkoutModalOpen = false;

                        this.lastSale = {
                            order_id: data.order_id,
                            invoice_no: data.invoice_no || ('#' + data.order_id),
                            paid_amount: data.paid_amount ?? paidSnap,
                            change: changeSnap,
                            discount_amount: data.discount_amount ?? lessSnap,
                            total_amount: data.total_amount ?? 0,
                            customer: customerSnap,
                            receipt_url: data.receipt_url || ('/pos/receipt/' + data.order_id),
                            receipt_html: null,
                            offline: false,
                        };
                        this.invoiceModalOpen = true;
                        if (lessSnap > 0) {
                            this.showToast('Sale complete! Less: Tk' + this.formatNumber(lessSnap), 'success');
                        } else {
                            this.showToast('Sale complete! Change to return: Tk' + this.formatNumber(changeSnap), 'success');
                        }
                    } else {
                        this.playBeep(false);
                        this.showToast('Error: ' + (data.message || 'Something went wrong'), 'error');
                    }
                } catch(e) {
                    // Only queue offline if we never got a successful JSON body
                    // (avoid duplicate when server committed but response was lost —
                    // client_uuid + unique constraint makes resync safe).
                    this.isOnline = false;
                    this.saveOfflineSale(payload);
                }
            } else {
                this.saveOfflineSale(payload);
            }

            this.isProcessing = false;
        },

        saveOfflineSale(payload) {
            if (this.isExchangeMode) {
                this.showToast('Cannot process exchanges while offline.', 'error');
                return;
            }

            const paidSnap = this.getPaidAmount();
            const lessSnap = this.getLessAmount();
            const changeSnap = this.getChange();
            const customerSnap = this.customerName || 'Walk-in Customer';
            const invoiceNo = 'OFF-' + Date.now().toString().slice(-10);
            const cartSnap = JSON.parse(JSON.stringify(this.cart));

            payload.local_invoice_no = invoiceNo;
            payload.items = cartSnap;
            payload.cart = cartSnap;

            const offline = JSON.parse(localStorage.getItem('nexa_offline_orders')) || [];
            offline.push(payload);
            localStorage.setItem('nexa_offline_orders', JSON.stringify(offline));
            this.offlinePendingTick++;

            // Reduce local stock so POS stays accurate until sync
            cartSnap.forEach(item => {
                const product = this.products.find(p => p.id === item.id);
                if (product) {
                    product.stock_quantity = Math.max(0, (Number(product.stock_quantity) || 0) - (Number(item.qty) || 0));
                }
            });

            const receiptHtml = this.buildOfflineReceiptHtml({
                invoiceNo,
                customer: customerSnap,
                phone: this.customerPhone || '',
                items: cartSnap,
                total: this.getTotal(),
                discount: this.getTotalDiscountAmount(),
                payable: this.getPayableTotal(),
                paid: paidSnap,
                change: changeSnap,
                method: this.getPaymentMethodString(),
                createdAt: payload.created_at,
            });

            this.playBeep(true);
            this.cart = []; this.customerName = ''; this.customerPhone = '';
            this.discountValue = 0; this.appliedCoupon = null; this.couponCode = '';
            this.lastAddedId = null;
            this.checkoutModalOpen = false;

            this.lastSale = {
                order_id: null,
                invoice_no: invoiceNo,
                paid_amount: paidSnap,
                change: changeSnap,
                discount_amount: payload.discount_amount || 0,
                total_amount: payload.total_amount,
                customer: customerSnap,
                receipt_url: null,
                receipt_html: receiptHtml,
                offline: true,
            };
            this.invoiceModalOpen = true;
            this.showToast('Offline bill saved — print now. Sync when network returns.', 'warning');
        },

        buildOfflineReceiptHtml(data) {
            const esc = (s) => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const money = (n) => this.formatNumber(n);
            const when = new Date(data.createdAt || Date.now());
            const fmt = { timeZone: 'Asia/Dhaka', day: '2-digit', month: 'short', year: 'numeric' };
            const fmtTime = { timeZone: 'Asia/Dhaka', hour: '2-digit', minute: '2-digit', hour12: true };
            const dateStr = when.toLocaleDateString('en-GB', fmt);
            const timeStr = when.toLocaleTimeString('en-GB', fmtTime);
            const printed = new Date().toLocaleString('en-GB', {
                timeZone: 'Asia/Dhaka', day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true,
            });
            const rows = (data.items || []).map(i => `
                <tr>
                    <td class="item-name">${esc(i.name || 'Product')}</td>
                    <td class="text-center">${Number(i.qty) || 0}</td>
                    <td class="text-right">${money(i.price)}</td>
                    <td class="text-right">${money((Number(i.price) || 0) * (Number(i.qty) || 0))}</td>
                </tr>`).join('');

            return `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice ${esc(data.invoiceNo)}</title>
<style>
*{box-sizing:border-box}
body{font-family:Segoe UI,Tahoma,sans-serif;font-size:12px;color:#0f172a;margin:0;padding:0;background:#fff}
.sheet{width:80mm;max-width:80mm;margin:0 auto;padding:12px}
.brand{text-align:center;border-bottom:2px solid #0f172a;padding-bottom:8px;margin-bottom:8px}
.brand .doc{font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#64748b}
.brand h1{margin:4px 0 0;font-size:16px;font-weight:800}
.meta{width:100%;border-collapse:collapse;margin-bottom:8px;font-size:11px}
.meta td{padding:2px 0}.meta .lbl{color:#64748b;font-weight:600}.meta .val{text-align:right;font-weight:700}
.party{background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;padding:8px;margin-bottom:8px}
.party .eyebrow{font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#64748b}
.party .name{font-weight:800;margin:2px 0}
.section{font-size:9px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #cbd5e1;padding-bottom:3px;margin:6px 0 4px}
table.items{width:100%;border-collapse:collapse}
table.items th{font-size:9px;font-weight:800;text-transform:uppercase;color:#64748b;border-bottom:1.5px solid #0f172a;padding:4px 0}
table.items td{padding:5px 0;border-bottom:1px dotted #e2e8f0;vertical-align:top;font-size:11px}
.item-name{font-weight:700}.text-center{text-align:center}.text-right{text-align:right}
.totals{width:100%;border-collapse:collapse;border-top:1.5px solid #0f172a;margin-top:4px}
.totals td{padding:3px 0;font-size:11px}.totals .lbl{color:#64748b}
.totals .grand td{padding-top:7px;font-size:13px;font-weight:800;border-top:1px dashed #0f172a}
.pay{margin-top:8px;border:1px solid #cbd5e1;border-radius:6px;padding:7px 9px;background:#f8fafc}
.pay table{width:100%}.pay td{padding:2px 0;font-size:11px}
.footer{text-align:center;margin-top:12px;padding-top:10px;border-top:2px solid #0f172a}
.footer .thanks{font-weight:800;margin:0 0 4px}.footer .note{margin:0;font-size:10px;color:#64748b}
.footer .stamp{margin-top:8px;font-size:9px;color:#64748b;font-family:ui-monospace,Consolas,monospace}
.badge{text-align:center;font-weight:800;border:2px dashed #b45309;color:#b45309;padding:5px;margin-bottom:8px;font-size:11px;letter-spacing:.06em}
</style></head><body>
<div class="sheet">
<div class="brand"><div class="doc">Sales Invoice / POS Receipt</div><h1>${esc(this.shopName)}</h1></div>
<div class="badge">*** OFFLINE BILL ***</div>
<table class="meta">
<tr><td class="lbl">Invoice No</td><td class="val">${esc(data.invoiceNo)}</td></tr>
<tr><td class="lbl">Date</td><td class="val">${esc(dateStr)}</td></tr>
<tr><td class="lbl">Time</td><td class="val">${esc(timeStr)} (BST)</td></tr>
<tr><td class="lbl">Cashier</td><td class="val">${esc(this.cashierName)}</td></tr>
<tr><td class="lbl">Status</td><td class="val">PENDING SYNC</td></tr>
</table>
<div class="party"><div class="eyebrow">Bill To</div><p class="name">${esc(data.customer || 'Walk-in Customer')}</p>
${data.phone ? `<p>Phone: ${esc(data.phone)}</p>` : ''}</div>
<div class="section">Items</div>
<table class="items">
<thead><tr><th class="text-left">Description</th><th class="text-center">Qty</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
<tbody>${rows}</tbody>
</table>
<table class="totals">
<tr><td class="lbl">Subtotal</td><td class="text-right">৳${money(data.total)}</td></tr>
${(data.discount || 0) > 0 ? `<tr><td class="lbl">Discount</td><td class="text-right">- ৳${money(data.discount)}</td></tr>` : ''}
<tr class="grand"><td>Grand total</td><td class="text-right">৳${money(data.payable)}</td></tr>
</table>
<div class="pay"><table>
<tr><td class="lbl">Payment method</td><td class="text-right" style="font-weight:700">${esc(String(data.method || 'cash').toUpperCase())}</td></tr>
<tr><td class="lbl">Amount paid</td><td class="text-right" style="font-weight:700">৳${money(data.paid)}</td></tr>
${(data.change || 0) > 0 ? `<tr><td class="lbl">Change due</td><td class="text-right" style="font-weight:800">৳${money(data.change)}</td></tr>` : ''}
</table></div>
<div class="footer">
<p class="thanks">Thank you for your business</p>
<p class="note">Will sync when network is available. Please retain this invoice.</p>
<p class="stamp">Printed: ${esc(printed)} · Asia/Dhaka</p>
</div>
</div></body></html>`;
        },

        printLastReceipt() {
            if (this.lastSale?.receipt_html) {
                this.printHtmlDocument(this.lastSale.receipt_html);
                return;
            }
            if (!this.lastSale?.receipt_url) {
                this.showToast('No receipt available to print', 'error');
                return;
            }
            // Prefer a dedicated print window — iframe.contentWindow.print() often
            // prints the blank POS shell (title "POS Terminal") in Chromium.
            this.printReceiptUrl(this.lastSale.receipt_url);
        },

        printReceiptUrl(url) {
            const printUrl = url + (url.includes('?') ? '&' : '?') + 'print=1';
            const w = window.open(printUrl, 'nexa_pos_receipt', 'width=440,height=720');
            if (!w) {
                this.showToast('Allow pop-ups to print receipts', 'error');
                // Last resort: navigate iframe then try after load
                const frame = this.$refs.receiptFrame;
                if (frame) {
                    frame.onload = () => {
                        try {
                            frame.contentWindow?.focus();
                            frame.contentWindow?.print();
                        } catch (e) {}
                    };
                    frame.src = printUrl;
                }
                return;
            }
            // Receipt view auto-prints when ?print=1; also retry after load for stubborn browsers
            const tryPrint = () => {
                try { w.focus(); w.print(); } catch (e) {}
            };
            w.addEventListener?.('load', () => setTimeout(tryPrint, 150));
            setTimeout(tryPrint, 600);
        },

        printHtmlDocument(html) {
            const w = window.open('', 'nexa_pos_receipt', 'width=440,height=720');
            if (!w) {
                this.showToast('Allow pop-ups to print receipts', 'error');
                return;
            }
            w.document.open();
            w.document.write(html);
            w.document.close();
            const tryPrint = () => {
                try { w.focus(); w.print(); } catch (e) {}
            };
            if (w.document.readyState === 'complete') {
                setTimeout(tryPrint, 150);
            } else {
                w.addEventListener('load', () => setTimeout(tryPrint, 150));
                setTimeout(tryPrint, 600);
            }
        },

        closeInvoiceModal() {
            this.invoiceModalOpen = false;
            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },

        /* ───────────────────────────────
           OFFLINE SYNC (ask permission first)
        ─────────────────────────────── */
        pendingOfflineCount() {
            this.offlinePendingTick; // dependency for Alpine reactivity
            try {
                return (JSON.parse(localStorage.getItem('nexa_offline_orders')) || []).length;
            } catch (e) {
                return 0;
            }
        },
        selectedCounterLabel() {
            const c = (this.posCounters || []).find(x => String(x.id) === String(this.selectedCounterId));
            if (!c) return 'Select counter';
            return c.has_open_session ? c.name : (c.name + ' (closed)');
        },
        hasOpenAdminCounter() {
            if (!this.isAdminPos) return true;
            const c = (this.posCounters || []).find(x => String(x.id) === String(this.selectedCounterId));
            return !!(c && c.has_open_session);
        },
        async confirmSyncOffline() {
            this.syncPromptOpen = false;
            await this.syncOfflineOrders();
        },
        async syncOfflineOrders() {
            const offline = JSON.parse(localStorage.getItem('nexa_offline_orders')) || [];
            if (!offline.length || this.isSyncing) return;
            this.isSyncing = true;
            try {
                const res  = await fetch('{{ route('pos.sync') }}', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body:    JSON.stringify({
                        orders: offline,
                        counter_id: this.isAdminPos ? (Number(this.selectedCounterId) || null) : null,
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    localStorage.removeItem('nexa_offline_orders');
                    this.offlinePendingTick++;
                    this.showToast(`Synced ${data.synced} offline bill(s)!`, 'success');
                    setTimeout(() => window.location.reload(), 1600);
                } else {
                    this.showToast(data.message || 'Sync failed. Try again.', 'error');
                }
            } catch(e) {
                this.showToast('Sync failed. Check network and try again.', 'error');
            }
            this.isSyncing = false;
        }
    };
}
</script>
</x-pos-layout>