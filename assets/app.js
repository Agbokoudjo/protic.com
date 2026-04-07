import { registerReactControllerComponents } from "vite-plugin-symfony/stimulus/helpers/react"
import { startStimulusApp } from "vite-plugin-symfony/stimulus/helpers"
import '@vitejs/plugin-react/preamble';
import './styles/app.css';
import 'bootstrap/dist/js/bootstrap.min.js'
import { disableUserInteractions } from './utils';
import { mountFooter } from "./react/lazy-mount.jsx"
import jQuery from 'jquery';
import { Logger } from "@wlindabla/form_validator";

window.jQuery = jQuery;
window.$ = jQuery;
Logger.config("dev",true)
const app = startStimulusApp();
registerReactControllerComponents(import.meta.glob('./react/controllers/**/*.js(x)\?',{ eager: true })); 
import.meta.glob('./images/**/*', { eager: true });

window.addEventListener('DOMContentLoaded', () => {
    disableUserInteractions("iws-config");
    mountFooter();
    SpeedometerScroll();
    bootstrapHandler();
    window.AOS.refresh();
    window.jQuery = window.$ = jQuery;
})

window.addEventListener('turbo:load', () => {
    disableUserInteractions("iws-config");
    mountFooter();
    SpeedometerScroll();
    bootstrapHandler()
    window.AOS.refresh();
})

function navOffcanvas() {
    const offcanvasEl = document.getElementById('navOffcanvas');
    if (!offcanvasEl) return;

    document.querySelectorAll('.js-offcanvas-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const href = link.getAttribute('href');

            // Ne rien faire pour les ancres vides
            if (!href || href === '#') return;

            e.preventDefault();

            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);

            if (bsOffcanvas) {
                // Ferme l'offcanvas puis navigue après la transition (300ms)
                offcanvasEl.addEventListener('hidden.bs.offcanvas', function handler() {
                    offcanvasEl.removeEventListener('hidden.bs.offcanvas', handler);
                    window.location.href = href;
                }, { once: true });

                bsOffcanvas.hide();
            } else {
                // Offcanvas pas ouvert — naviguer directement
                window.location.href = href;
            }
        });
    });
}

function SpeedometerScroll() {
    let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop;
    let lastTimestamp = Date.now();
    let scrollTimeout;

    // Configuration
    const SPEED_THRESHOLD = 5; // Ajuste cette valeur (ex: 2.5px/ms est déjà très rapide)
    const TOOLTIP_ID = 'scroll-speed-alert';

    // Création de l'infobulle (Toast)
    const tooltip = document.createElement('div');
    tooltip.id = TOOLTIP_ID;
    tooltip.innerHTML = "📖 Prenez le temps de lire, défilez plus doucement !";
    Object.assign(tooltip.style, {
        position: 'fixed',
        bottom: '20px',
        left: '50%',
        transform: 'translateX(-50%) translateY(100px)',
        backgroundColor: '#2D3099', // Ta couleur d'agence
        color: '#fff',
        padding: '12px 24px',
        borderRadius: '30px',
        boxShadow: '0 4px 15px rgba(0,0,0,0.3)',
        zIndex: '10000',
        transition: 'transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
        fontFamily: 'sans-serif',
        pointerEvents: 'none'
    });
    document.body.appendChild(tooltip);

    window.addEventListener('scroll', () => {
        const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const currentTimestamp = Date.now();

        const distance = Math.abs(currentScrollTop - lastScrollTop);
        const timeElapsed = currentTimestamp - lastTimestamp;

        if (timeElapsed > 0) {
            const speed = distance / timeElapsed;

            if (speed > SPEED_THRESHOLD) {
                showAlert();
            }
        }

        lastScrollTop = currentScrollTop;
        lastTimestamp = currentTimestamp;
    }, { passive: true });

    function showAlert() {
        tooltip.style.transform = 'translateX(-50%) translateY(0)';
        
        // Cacher l'infobulle après 2 secondes d'inactivité
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            tooltip.style.transform = 'translateX(-50%) translateY(100px)';
        }, 2000);
    }
}


function bootstrapHandler() {
        if (typeof bootstrap !== 'undefined') {
        // 1. Initialisation des dropdowns
        const dropdownToggleList = jQuery('[data-bs-toggle="dropdown"]');
        dropdownToggleList.each(function (_, dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
        });
    
        // 2. Initialisation des éléments collapsibles
        const collapseToggleList = jQuery('[data-bs-toggle="collapse"]');
        collapseToggleList.each(function (_, collapseToggleEl) {
            new bootstrap.Collapse(collapseToggleEl, {
                toggle: false // Empêche l'ouverture automatique
            });
        });
    
        // 3. Initialisation des tooltips (infobulles)
        const tooltipTriggerList = jQuery('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.each(function (_, tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    
        // 4. Initialisation des popovers
        const popoverTriggerList = jQuery('[data-bs-toggle="popover"]');
        popoverTriggerList.each(function (_, popoverTriggerEl) {
            new bootstrap.Popover(popoverTriggerEl);
        });
    } else {
        console.warn("Bootstrap object not found. Make sure Bootstrap CDN is loaded before your custom script.");
    }
    }