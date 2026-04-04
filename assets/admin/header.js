export function initHeader() {
    const header = document.getElementById('app-header');
    if (!header) return;

    // Orbe gauche
    const orbLeft = document.createElement('div');
    orbLeft.style.cssText = `
        position: absolute;
        top: -25px; left: 50px;
        width: 90px; height: 90px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(45,48,153,0.22) 0%, transparent 70%);
        animation: orb-pulse 6s ease-in-out infinite;
        pointer-events: none;
        z-index: 0;
    `;
    header.insertBefore(orbLeft, header.firstChild);

    // Orbe droite
    const orbRight = document.createElement('div');
    orbRight.style.cssText = `
        position: absolute;
        top: -12px; right: 100px;
        width: 65px; height: 65px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
        animation: orb-pulse 8s ease-in-out infinite 2.5s;
        pointer-events: none;
        z-index: 0;
    `;
    header.insertBefore(orbRight, header.firstChild);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeader);
} else {
    initHeader();
}