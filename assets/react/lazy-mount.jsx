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

const roots = new Map();

/**
 * Monte un composant React quand son root entre dans le viewport
 */
const lazyMount = (rootEl) => {
    // Déjà monté — on ignore
    if (rootEl.dataset.mounted === "true") return;
    if (roots.has(rootEl)) return;

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
            roots.set(rootEl, root);
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
    const rootEl = document.getElementById('protic-footer-root');
    
    // Sécurité : si le root n'existe pas OU s'il est déjà marqué comme monté
    if (!rootEl || rootEl.dataset.mounted === 'true') return;

    const raw = rootEl.getAttribute('data-config');
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
            if (rootEl.dataset.mounted === 'true') return;
            rootEl.dataset.mounted = 'true';

            const reactRoot = createRoot(rootEl);
            reactRoot.render(<Footer config={config} />);

            /* Relance AOS pour les éléments nouvellement insérés */
            if (window.AOS) {
                // Petit timeout pour laisser à React le temps de rendre le DOM
                setTimeout(() => window.AOS.refresh(), 100);
            }
        },
        { rootMargin: '200px' } /* pré-charge 200px avant d'entrer dans le viewport */
    );

    observer.observe(rootEl);
};
