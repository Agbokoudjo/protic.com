import React from "react";
import { createRoot } from "react-dom/client";
import BooksGrid from "./controllers/BooksGrid";
import CatalogueGrid from './controllers/CatalogueGrid';
import Footer from "./controllers/Footer";
/**
 * Map des composants disponibles pour le lazy mount
 * Ajouter ici chaque composant que vous voulez monter lazily
 */
const COMPONENTS = {
    BooksGrid,
};

/**
 * Monte un composant React quand son root entre dans le viewport
 */
const lazyMount = (rootEl) => {
    // Déjà monté — on ignore
    if (rootEl.dataset.mounted === "true") return;

    const componentName = rootEl.dataset.component;
    const Component     = COMPONENTS[componentName];

    if (!Component) {
        console.warn(`[lazy-mount] Composant inconnu : "${componentName}"`);
        return;
    }

    // Lecture des props depuis les data-attributes
    const props = {
        limit: parseInt(rootEl.dataset.limit ?? "12", 10),
        mode:  rootEl.dataset.mode ?? "catalogue",
    };

    const observer = new IntersectionObserver(
        ([entry], obs) => {
            if (!entry.isIntersecting) return;

            // On arrête d'observer — on ne monte qu'une fois
            obs.disconnect();
            rootEl.dataset.mounted = "true";

            // Montage React
            const root = createRoot(rootEl);
            root.render(<Component {...props} />);
        },
        {
            rootMargin: "200px", // commence à charger 200px avant que le div soit visible
            threshold: 0,
        }
    );

    observer.observe(rootEl);
};

/**
 * Initialise tous les roots présents dans la page
 */
export const initLazyMounts = () => {
    document
        .querySelectorAll("[data-component]")
        .forEach(lazyMount);
};

/* ══════════════════════════════════════════════════════════════
   Montage React — CatalogueGrid
══════════════════════════════════════════════════════════════ */
export const mountCatalogue = () => {
    const root = document.getElementById('catalogue-books-root');
    if (!root || root.dataset.mounted) return;
    root.dataset.mounted = 'true';

    const perPage = parseInt(root.dataset.perPage ?? '12', 10);
    createRoot(root).render(<CatalogueGrid itemsPerPage={perPage} />);
};

export const mountFooter = () => {
    const root = document.getElementById('protic-footer-root');
    if (!root) return;

    const raw = root.getAttribute('data-config');
    if (!raw) return;
    let config;
    try {
        config = JSON.parse(raw);
    } catch (e) {
        console.error('[ProTIC Footer] Erreur de parsing data-config :', e);
        return;
    }

    const observer = new IntersectionObserver(
        ([entry], obs) => {
            if (!entry.isIntersecting) return;
            obs.disconnect();
            createRoot(root).render(<Footer config={config} />);
            /* Relance AOS pour les éléments nouvellement insérés */
            if (window.AOS) window.AOS.refresh();
        },
        { rootMargin: '200px' } /* pré-charge 200px avant d'entrer dans le viewport */
    );

    observer.observe(root);
};
