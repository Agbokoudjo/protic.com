function initMain() {
    const main = document.getElementById('app-main');
    if (!main) return;

    // Couche de fond
    const bgLayer = document.createElement('div');
    bgLayer.className = 'main-bg-layer';
    main.insertBefore(bgLayer, main.firstChild);

    // 3 vagues
    ['main-wave-1', 'main-wave-2', 'main-wave-3'].forEach(cls => {
        const el = document.createElement('div');
        el.className = cls;
        main.insertBefore(el, main.firstChild);
    });

    // 3 orbes flottants
    ['main-orb-1', 'main-orb-2', 'main-orb-3'].forEach(cls => {
        const el = document.createElement('div');
        el.className = cls;
        main.insertBefore(el, main.firstChild);
    });
}

// ── Init ──────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMain);
} else {
    initMain();
}

export { initMain };
