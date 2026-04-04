
import './styles/book.css'
import './styles/catalogue.css';

import { initLazyMounts,mountCatalogue  } from "./react/lazy-mount.jsx";

/* ══════════════════════════════════════════════════════════════
   Helpers — dispatch events vers React
══════════════════════════════════════════════════════════════ */
const dispatch = (type, detail) =>
    document.dispatchEvent(new CustomEvent(type, { detail }));

/* ══════════════════════════════════════════════════════════════
   Recherche — Hero search + Sidebar search
══════════════════════════════════════════════════════════════ */
const initSearch = () => {
    // Sidebar
    const sidebarInput = document.getElementById('cat-search-input');
    const clearBtn     = document.getElementById('cat-search-clear');
    const suggestion   = document.getElementById('cat-search-suggestion');
    const suggText     = document.getElementById('cat-search-suggestion-text');

    if (sidebarInput) {
        sidebarInput.addEventListener('input', () => {
            const q = sidebarInput.value.trim();
            dispatch('protic:catalogue:search', { query: q });

            // Bouton clear
            if (clearBtn) clearBtn.hidden = q.length === 0;

            // Suggestion live
            if (suggestion && suggText) {
                if (q.length >= 2) {
                    suggText.textContent = `Recherche en cours pour « ${q } »…`;
                    suggestion.hidden = false;
                } else {
                    suggestion.hidden = true;
                }
            }
            updateActiveCount();
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                sidebarInput.value = '';
                sidebarInput.dispatchEvent(new Event('input'));
                sidebarInput.focus();
            });
        }
    }

    // Hero search
    const heroInput = document.getElementById('cat-hero-search');
    const heroBtn   = document.querySelector('.cat-hero__search-btn');

    const triggerHeroSearch = () => {
        const q = heroInput?.value.trim() ?? '';
        if (!q) return;
        dispatch('protic:catalogue:search', { query: q });
        // Scroll vers le catalogue
        document.getElementById('catalogue')?.scrollIntoView({ behavior: 'smooth' });
        // Sync avec sidebar
        if (sidebarInput) sidebarInput.value = q;
        updateActiveCount();
    };

    if (heroInput) {
        heroInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') triggerHeroSearch();
        });
    }
    if (heroBtn) heroBtn.addEventListener('click', triggerHeroSearch);

    // Pills hints hero
    document.querySelectorAll('.js-quick-search').forEach(btn => {
        btn.addEventListener('click', () => {
            const q = btn.dataset.q ?? '';
            if (heroInput) heroInput.value = q;
            dispatch('protic:catalogue:search', { query: q });
            document.getElementById('catalogue')?.scrollIntoView({ behavior: 'smooth' });
            if (sidebarInput) sidebarInput.value = q;
            updateActiveCount();
        });
    });
};

/* ══════════════════════════════════════════════════════════════
   Filtres Genre — Pills sidebar + Genre bar + Genre showcase
══════════════════════════════════════════════════════════════ */
const initGenreFilters = () => {
    let activeGenre = '';

    const syncPills = (genre) => {
        // Toutes les pills genre (sidebar chips + genre-bar + genre-showcase)
        document.querySelectorAll('.js-filter-genre').forEach(input => {
            input.checked = input.value === genre;
        });
        document.querySelectorAll('.js-genre-pill').forEach(btn => {
            const isAll = btn.dataset.genre === '';
            btn.classList.toggle('is-active', btn.dataset.genre === genre || (isAll && genre === ''));
            btn.setAttribute('aria-pressed', btn.dataset.genre === genre || (isAll && genre === '') ? 'true' : 'false');
        });
    };

    // Sidebar chips
    document.querySelectorAll('.js-filter-genre').forEach(input => {
        input.addEventListener('change', () => {
            activeGenre = input.checked ? input.value : '';
            // Décocher les autres
            document.querySelectorAll('.js-filter-genre').forEach(other => {
                if (other !== input) other.checked = false;
            });
            dispatch('protic:catalogue:genre', { genre: activeGenre });
            syncPills(activeGenre);
            updateActiveCount();
        });
    });

    // Genre pills (genre-bar + genres showcase)
    document.querySelectorAll('.js-genre-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            activeGenre = btn.dataset.genre ?? '';
            dispatch('protic:catalogue:genre', { genre: activeGenre });
            syncPills(activeGenre);
            // Scroll vers catalogue
            document.getElementById('catalogue')?.scrollIntoView({ behavior: 'smooth' });
            updateActiveCount();
        });
    });
};

/* ══════════════════════════════════════════════════════════════
   Tri
══════════════════════════════════════════════════════════════ */
const initSort = () => {
    const select = document.getElementById('cat-sort-select');
    if (!select) return;
    select.addEventListener('change', () => {
        dispatch('protic:catalogue:sort', { sort: select.value });
    });
};

/* ══════════════════════════════════════════════════════════════
   Vue grille / liste
══════════════════════════════════════════════════════════════ */
const initViewToggle = () => {
    const btnGrid = document.getElementById('btn-view-grid');
    const btnList = document.getElementById('btn-view-list');

    [btnGrid, btnList].forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', () => {
            [btnGrid, btnList].forEach(b => {
                b?.classList.remove('is-active');
                b?.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('is-active');
            btn.setAttribute('aria-pressed', 'true');
            dispatch('protic:catalogue:view', { view: btn.dataset.view });
        });
    });
};

/* ══════════════════════════════════════════════════════════════
   Reset
══════════════════════════════════════════════════════════════ */
const initReset = () => {
    const resetAll = () => {
        dispatch('protic:catalogue:reset', {});
        // Reset UI
        const sidebarInput = document.getElementById('cat-search-input');
        if (sidebarInput) sidebarInput.value = '';
        const clearBtn = document.getElementById('cat-search-clear');
        if (clearBtn) clearBtn.hidden = true;
        const suggestion = document.getElementById('cat-search-suggestion');
        if (suggestion) suggestion.hidden = true;
        document.querySelectorAll('.js-filter-genre').forEach(i => { i.checked = false; });
        document.querySelectorAll('.js-genre-pill').forEach(btn => {
            btn.classList.remove('is-active');
            btn.setAttribute('aria-pressed', 'false');
        });
        document.querySelector('.js-genre-pill[data-genre=""]')?.classList.add('is-active');
        const sortSelect = document.getElementById('cat-sort-select');
        if (sortSelect) sortSelect.value = 'date_desc';
        updateActiveCount();
    };

    document.getElementById('cat-reset-btn')?.addEventListener('click', resetAll);
    document.getElementById('cat-reset-btn-mobile')?.addEventListener('click', resetAll);
};

/* ══════════════════════════════════════════════════════════════
   Badge compteur filtres actifs
══════════════════════════════════════════════════════════════ */
const updateActiveCount = () => {
    const badge = document.getElementById('cat-active-count');
    if (!badge) return;

    const searchActive = (document.getElementById('cat-search-input')?.value.trim().length ?? 0) > 0;
    const genreActive  = Array.from(document.querySelectorAll('.js-filter-genre')).some(i => i.checked);
    const sortActive   = document.getElementById('cat-sort-select')?.value !== 'date_desc';

    const count = [searchActive, genreActive, sortActive].filter(Boolean).length;
    badge.textContent = count;
    badge.hidden = count === 0;
};

/* ══════════════════════════════════════════════════════════════
   Sidebar mobile toggle
══════════════════════════════════════════════════════════════ */
const initSidebarToggle = () => {
    const toggle  = document.getElementById('cat-filter-toggle');
    const body    = document.getElementById('cat-filters-body');
    const chevron = document.getElementById('cat-chevron');

    if (!toggle || !body) return;

    toggle.addEventListener('click', () => {
        const isOpen = body.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen);
        chevron?.classList.toggle('is-rotated', isOpen);
    });
};

/* ══════════════════════════════════════════════════════════════
   Init
══════════════════════════════════════════════════════════════ */
const init = () => {
    mountCatalogue();
    initSearch();
    initGenreFilters();
    initSort();
    initViewToggle();
    initReset();
    initSidebarToggle();
};

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('turbo:load', init);
