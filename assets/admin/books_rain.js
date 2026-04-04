// ============================================================
// assets/admin/books-rain.js
// Animation livres qui tombent — uniquement dans #app-main
// Compatible Vite + AdminLTE / SonataAdmin
// ============================================================

const CONFIG = {
    total       : 25,
    emojis      : ['📖', '📚', '📕', '📗', '📘', '📝', '✍️', '🖊️', '📄', '🗒️'],
    minSize     : 20,
    maxSize     : 36,
    minDuration : 7,
    maxDuration : 18,
};

// ── Injecter le CSS ───────────────────────────────────────
function injectCSS() {
    if (document.getElementById('books-rain-css')) return;
    const style = document.createElement('style');
    style.id = 'books-rain-css';
    style.textContent = `
        @keyframes book-fall {
            0%   { transform: translateY(-80px) rotate(var(--rs)) scale(0.8); opacity: 0; }
            8%   { opacity: var(--op); }
            90%  { opacity: var(--op); }
            100% { transform: translateY(105vh) rotate(var(--re)) scale(0.9); opacity: 0; }
        }
        @keyframes book-sway {
            0%   { margin-left: 0; }
            25%  { margin-left: var(--sr); }
            75%  { margin-left: var(--sl); }
            100% { margin-left: 0; }
        }
        #books-rain-container {
            position: fixed !important;
            inset: 0 !important;
            pointer-events: none !important;
            z-index: 4 !important;
            overflow: hidden !important;
        }
        .b-item {
            position: fixed !important;
            top: -80px !important;
            font-size: var(--sz);
            opacity: 0;
            animation:
                book-fall var(--dur) linear infinite var(--del),
                book-sway calc(var(--dur) * 0.55) ease-in-out infinite var(--del);
            will-change: transform, opacity;
            filter: drop-shadow(0 0 8px rgba(100,160,255,0.6)) drop-shadow(0 2px 4px rgba(0,0,0,0.8));
            user-select: none;
            line-height: 1;
            z-index: 4 !important;
        }
    `;
    document.head.appendChild(style);
}

// ── Créer un item ─────────────────────────────────────────
function makeItem() {
    const el      = document.createElement('div');
    const emoji   = CONFIG.emojis[Math.floor(Math.random() * CONFIG.emojis.length)];
    const size    = CONFIG.minSize + Math.random() * (CONFIG.maxSize - CONFIG.minSize);
    const dur     = CONFIG.minDuration + Math.random() * (CONFIG.maxDuration - CONFIG.minDuration);
    const del     = -(Math.random() * CONFIG.maxDuration);
    const left    = Math.random() * 95;
    const opacity = 0.40 + Math.random() * 0.35;
    const rotS    = -30 + Math.random() * 60;
    const rotE    = rotS + (Math.random() - 0.5) * 80;
    const swayR   = `${6 + Math.random() * 18}px`;
    const swayL   = `-${6 + Math.random() * 18}px`;

    el.className   = 'b-item';
    el.textContent = emoji;
    el.style.cssText = `
        left:${left.toFixed(1)}%;
        --sz:${size.toFixed(0)}px;
        --dur:${dur.toFixed(1)}s;
        --del:${del.toFixed(1)}s;
        --op:${opacity.toFixed(2)};
        --rs:${rotS.toFixed(0)}deg;
        --re:${rotE.toFixed(0)}deg;
        --sr:${swayR};
        --sl:${swayL};
    `;
    return el;
}

// ── Init ──────────────────────────────────────────────────
function initBooksRain() {
    const main = document.getElementById('app-main');
    if (!main) return;

    // Éviter une double initialisation
    if (document.getElementById('books-rain-container')) return;

    injectCSS();

    // Attacher le container directement au body pour échapper
    // aux contraintes z-index / overflow de #app-main
    const container = document.createElement('div');
    container.id = 'books-rain-container';

    for (let i = 0; i < CONFIG.total; i++) {
        container.appendChild(makeItem());
    }

    // ← Inséré dans body, pas dans #app-main
    document.body.appendChild(container);

    // Pause si onglet inactif
    document.addEventListener('visibilitychange', () => {
        const state = document.hidden ? 'paused' : 'running';
        container.querySelectorAll('.b-item').forEach(el => {
            el.style.animationPlayState = state;
        });
    });

    console.log(`✅ Books rain init — ${CONFIG.total} items dans body (fixed sur #app-main)`);
}

// ── Attendre que #app-main soit dans le DOM ───────────────
function waitForAppMain() {
    if (document.getElementById('app-main')) {
        initBooksRain();
        return;
    }

    const observer = new MutationObserver(() => {
        if (document.getElementById('app-main')) {
            observer.disconnect();
            initBooksRain();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    setTimeout(() => {
        observer.disconnect();
        console.warn('⚠️ Books rain : #app-main introuvable après 10s');
    }, 10000);
}

// ── Entry point ───────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForAppMain);
} else {
    waitForAppMain();
}