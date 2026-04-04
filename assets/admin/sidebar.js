// ============================================================
// assets/admin/sidebar.js
// Génération dynamique des particules et vagues du sidebar
// ============================================================

const CONFIG = {
    gold  : 8,
    white : 5,
    blue  : 4,
    orbs  : [
        { size:'90px',  top:'12%',  left:'-20px',  color:'rgba(255,215,0,0.06)',  breathe:'6s',  delay:'0s'   },
        { size:'70px',  top:'52%',  right:'-15px', color:'rgba(45,48,153,0.12)',  breathe:'9s',  delay:'2s'   },
        { size:'50px',  top:'78%',  left:'30%',    color:'rgba(255,215,0,0.05)',  breathe:'5s',  delay:'3.5s' },
    ]
};

// ── Créer une particule ───────────────────────────────────
function makeParticle(type, opts) {
    const el = document.createElement('div');
    el.className = `sidebar-particle ${type}`;
    el.style.cssText = `
        left: ${opts.left};
        bottom: ${opts.bottom};
        --size: ${opts.size};
        --duration: ${opts.duration};
        --delay: ${opts.delay};
        --drift: ${opts.drift};
    `;
    return el;
}

// ── Créer un orbe ─────────────────────────────────────────
function makeOrb(cfg) {
    const el = document.createElement('div');
    el.className = 'sidebar-orb';
    el.style.cssText = `
        width: ${cfg.size};
        height: ${cfg.size};
        ${cfg.top   ? `top: ${cfg.top};`     : ''}
        ${cfg.bottom? `bottom: ${cfg.bottom};`: ''}
        ${cfg.left  ? `left: ${cfg.left};`   : ''}
        ${cfg.right ? `right: ${cfg.right};` : ''}
        background: radial-gradient(circle, ${cfg.color}, transparent 70%);
        --breathe: ${cfg.breathe};
        --delay: ${cfg.delay};
    `;
    return el;
}

// ── Init sidebar animations ───────────────────────────────
function initSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    if (!sidebar) return;

    // Conteneur particules
    const container = document.createElement('div');
    container.id = 'sidebar-particles';
    sidebar.insertBefore(container, sidebar.firstChild);

    // Particules dorées
    for (let i = 0; i < CONFIG.gold; i++) {
        container.appendChild(makeParticle('gold', {
            left    : `${8  + Math.random() * 84}%`,
            bottom  : `${Math.random() * 18}%`,
            size    : `${2  + Math.random() * 2.5}px`,
            duration: `${5  + Math.random() * 6}s`,
            delay   : `${Math.random() * 6}s`,
            drift   : `${(Math.random() - 0.5) * 22}px`,
        }));
    }

    // Particules blanches
    for (let i = 0; i < CONFIG.white; i++) {
        container.appendChild(makeParticle('white', {
            left    : `${5  + Math.random() * 90}%`,
            bottom  : `${Math.random() * 12}%`,
            size    : `${1.5 + Math.random() * 2}px`,
            duration: `${7  + Math.random() * 5}s`,
            delay   : `${Math.random() * 8}s`,
            drift   : `${(Math.random() - 0.5) * 16}px`,
        }));
    }

    // Particules bleues
    for (let i = 0; i < CONFIG.blue; i++) {
        container.appendChild(makeParticle('blue', {
            left    : `${10 + Math.random() * 80}%`,
            bottom  : `${Math.random() * 10}%`,
            size    : `${1.5 + Math.random() * 1.5}px`,
            duration: `${8  + Math.random() * 6}s`,
            delay   : `${Math.random() * 10}s`,
            drift   : `${(Math.random() - 0.5) * 18}px`,
        }));
    }

    // Orbes ambiants
    CONFIG.orbs.forEach(cfg => container.appendChild(makeOrb(cfg)));

    // Pause si sidebar collapsé
    watchSidebarState(container);
}

// ── Observer état sidebar ─────────────────────────────────
function watchSidebarState(container) {
    const update = () => {
        const collapsed = document.body.classList.contains('sidebar-collapse');
        container.style.opacity    = collapsed ? '0' : '1';
        container.style.transition = 'opacity 0.3s ease';
    };
    new MutationObserver(update).observe(document.body, {
        attributes     : true,
        attributeFilter: ['class']
    });
    update();
}

// ── Entry point ───────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebar);
} else {
    initSidebar();
}

export { initSidebar };

