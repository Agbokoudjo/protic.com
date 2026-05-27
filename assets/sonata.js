import { startStimulusApp,registerControllers  } from "vite-plugin-symfony/stimulus/helpers"
import * as stimulus from '@hotwired/stimulus';
import 'bootstrap/dist/js/bootstrap.js'
import './styles/sonata.css';
import './styles/admin/flashmessage.css'
import './styles/admin/readmore.css'
import 'admin-lte/dist/js/adminlte.js';
import './styles/admin/variables.css';
import './styles/admin/sidebar.css';
import './styles/admin/sidebar_animations.css';
import './styles/admin/header.css';
import './styles/admin/main.css';
import './admin/sidebar.js';
import './admin/header.js';
import './admin/main.js';
import './admin/books_rain.js';
import './admin/batch-actions.js';
import './admin/sonata_user_form_extra.js'
import {basicLightboxImage,basicLightboxDocument} from  './basicLightbox.js';
import { SpaKernel,SpaEvents } from '@wlindabla/sonata_spa';

window.stimulus = stimulus;
const sonataApplication = startStimulusApp();
window.sonataApplication = sonataApplication;
sonataApplication.debug = true;

import {
  addParamToUrl,
    appTranslation,
  fetchErrorTranslator,
  eventDispatcherBrowser,
  FormSubmitRequestEvents,
  PrepareRequestFormSubmitEvent,
  showLoadingDialog,
  Logger
} from '@wlindabla/form_validator';
import { formformatterEventHandle } from "./form.js"
import {
  config,
  crudAccountHandle,
    crudUserAccountListener 
  } from "./utils.js";

import.meta.glob('./images/**/*', { eager: true });

    registerControllers(
    sonataApplication,
    import.meta.glob(
        "./controllers/*_controller.js",
        {
        eager: true,
        },
    ),
    );

    const sonataControllers = import.meta.glob(
    "../vendor/sonata-project/admin-bundle/assets/js/controllers/*_controller.[jt]s",
        { eager: true });
    Object.entries(sonataControllers).forEach(([filePath, module]) => {
    const match = filePath.match(/([^/]+)_controller\.[jt]sx?$/);
    if (!match) return;

    const name       = match[1].replace(/_/g, "-");
    const identifier = `sonata-${name}`;
    const controller = module.default;

    if (controller) {
        sonataApplication.register(identifier, controller);
    }
});


document.addEventListener('DOMContentLoaded', async () => {
        /**
       * @type string
       */
      const APP_ENV = config.param('APP_ENV', "sonata-config");
      /**
       * @type boolean
       */ 
      const DEBUG = config.param('DEBUG', "sonata-config");

      Logger.config(APP_ENV,DEBUG)
     // Récupère le hash actuel des traductions
    const currentHash = jQuery('meta[name="sonata-translations-hash"]').attr('content');
    const cachedHash = localStorage.getItem('sonata_translations_hash');
    
    // Si le hash a changé, vide le cache
    if (currentHash && cachedHash !== currentHash) {
        await appTranslation.clearCache();
        localStorage.setItem('sonata_translations_hash', currentHash);
    }

    await appTranslation.preload('sonata-translations');
    window.fetchErrorTranslator = fetchErrorTranslator 
     window.SonataTranslator = appTranslation;
    basicLightboxImage();
    basicLightboxDocument();
    localDatetime()
    crudAccountHandle();
    crudUserAccountListener();
    formformatterEventHandle() ;
  logoutProgramming();
   dashboard();
  _addEventListener() ;
    window.appRouter= SpaKernel.create(
    {
        router: {
            sidebarSelector: '.app-sidebar',
            mainContentAreaSelector: '#app-content',
            mainContentHeaderSelector: '#app-content-header',
        },
    },
   APP_ENV ,
    eventDispatcherBrowser
    ).boot();
  
});
  


function localDatetime() {
    // On cible tous les éléments <time> qui ont l'attribut datetime
    const times = document.querySelectorAll('time[datetime]');
    console.log(times )
    if (times.length === 0) return;

    // Récupération de la langue de la page (ex: <html lang="fr">)
    const locale = document.documentElement.lang || "fr"; 

    const formatter = new Intl.DateTimeFormat(locale, {
        dateStyle: "medium",
        timeStyle: "short",
    });

    times.forEach(time => {
        const dateValue = time.getAttribute('datetime');
        
        if (dateValue) {
            const date = new Date(dateValue);
            
            // On vérifie que la date est valide avant de formater
            if (!isNaN(date.getTime())) {
                time.textContent = formatter.format(date);
            }
        }
    });
}

document.addEventListener('click', function (event) {
    const toggle = event.target.closest('.sonata-toggle-filter');
    if (!toggle) return;

    const icon = toggle.querySelector('.filter-icon');
    
    // Si l'élément était déjà actif, on le désactive visuellement
    if (toggle.classList.contains('active')) {
        icon.classList.remove('fas', 'fa-check-square');
        icon.classList.add('fa-regular', 'fa-square');
        toggle.classList.remove('active');
    } else {
        // Sinon on l'active
        icon.classList.remove('fa-regular', 'fa-square');
        icon.classList.add('fas', 'fa-check-square');
        toggle.classList.add('active');
    }
});

function logoutProgramming() {
    const INACTIVITY_LIMIT_MS = 115 * 60 * 1000; // 1h55 en ms
    const LOGOUT_URL = '/admin/logout'; 

    let timer;

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(forceLogout, INACTIVITY_LIMIT_MS);
    }

    function forceLogout() {
        // Le firewall Symfony intercepte cette requête et déclenche le logout
        window.location.href = LOGOUT_URL;
    }

    // Événements utilisateur qui prouvent l'activité
    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt =>
        document.addEventListener(evt, resetTimer, { passive: true })
    );

    // Démarrer le timer au chargement
    resetTimer();
};

function _addEventListener() {
  /**
   * @param {PrepareRequestFormSubmitEvent} event
   */
  eventDispatcherBrowser.addListener(
    FormSubmitRequestEvents.FORM_SUBMIT_PREPARE_REQUEST,
    async (event) => {
      event.stopPropagation();
      
        showLoadingDialog({config:{
            title: await window.SonataTranslator.trans('FORM_SUBMISSION_PROGRESS_TITLE','sonata-translations'),
            text: await window.SonataTranslator.trans('FORM_SUBMISSION_PROGRESS_MESSAGE','sonata-translations')
        }})
    }, 100);
  
   eventDispatcherBrowser.addListener(SpaEvents.DOM_READY, (event) => {
    dashboard(event.routeMatch.url);
});
}

/**
 * Dashboard ProTIC Éditions & Services
 *
 * Stratégie : UN seul fetch vers /api/admin/dashboard/stats
 * au lieu de N appels individuels vers les collections API Platform.
 *
 * Avantages :
 *  - 6 requêtes réseau → 1
 *  - Côté serveur : 6 COUNT(*) SQL au lieu de 6 SELECT * avec hydration
 *  - Toutes les cartes s'animent simultanément à la réception
 *  - Un seul point de retry en cas d'erreur réseau
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
function dashboard(url) {
  if (!(/\/dashboard(\/.*)?(\?.*)?$/.test(url ?? window.location.href))) return;
  
    function updateDate() {
        const el = document.getElementById('dash-live-date');
        if (!el) return;
        el.textContent = new Date().toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        });
    }

    function animateCounter(el, target) {
        const duration = 900;
        const start    = performance.now();

        (function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const ease     = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
            el.textContent = Math.floor(ease * target);
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        })(start);
    }

    function setCardLoading(card) {
        const counter = card.querySelector('.counter');
        counter.classList.add('loading');
        counter.classList.remove('error');
        counter.textContent = '';
    }

    function setCardError(card) {
        const counter = card.querySelector('.counter');
        counter.classList.remove('loading');
        counter.classList.add('error');
        counter.textContent = '—';
    }

    function setCardSuccess(card, value) {
        const counter = card.querySelector('.counter');
        const link    = card.dataset.link;

        counter.classList.remove('loading', 'error');
        card.classList.add('loaded');

        animateCounter(counter, Number(value));

        if (link && !card.dataset.clickBound) {
            card.dataset.clickBound = '1';
            card.style.cursor = 'pointer';
            card.addEventListener('click', () => { window.location.href = link; });
        }
    }

    async function loadAllStats() {
        const cards = document.querySelectorAll('#stats-grid .stat-card[data-stat-key]');

        // Passer toutes les cartes en skeleton simultanément
        cards.forEach(setCardLoading);

        try {
            const res = await fetch('/api/admin/dashboard/stats', {
                headers: { 'Accept': 'application/json' },
            });

          if (!res.ok) {
              window.location.href = url;
             return 
            };

            /** @type {Record<string, number>} */
            const stats = await res.json();

            // Distribuer les valeurs — toutes les cartes s'animent en même temps
            cards.forEach(card => {
                const key   = card.dataset.statKey;   // ex. "authors"
                const value = stats[key];

                if (value !== undefined && value !== null) {
                    setCardSuccess(card, value);
                } else {
                    // La clé n'existe pas dans la réponse (edge case)
                    setCardError(card);
                    console.warn(`[Dashboard] Clé "${key}" absente de la réponse.`);
                }
            });

            // Mettre à jour le chip "dernière actualisation"
            const chip = document.getElementById('dash-last-refresh');
            if (chip) {
                const time = new Date().toLocaleTimeString('fr-FR', {
                    hour: '2-digit', minute: '2-digit',
                });
                chip.querySelector('span').textContent = `Actualisé à ${time}`;
            }

        } catch (err) {
            // Une seule erreur réseau → toutes les cartes en état erreur
            cards.forEach(setCardError);
            console.error('[Dashboard] Échec du chargement des statistiques :', err);
        }
    }

    function init() {
        updateDate();
        loadAllStats();

        // Refresh discret toutes les 5 minutes
        if (/\/dashboard(\/.*)?(\?.*)?$/.test(window.location.href)){
            setInterval(loadAllStats, 30 * 60 * 1000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}

