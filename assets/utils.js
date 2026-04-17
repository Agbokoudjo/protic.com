import {
    getMetaContent,
    Logger,
     CRUDActionConfirmationHandle,
    processCRUDAction
} from "@wlindabla/form_validator";
import TomSelect from 'tom-select';

class Config {
    #params = null;
    /**
     * @type Config
     */
    static #instance;

    /**
     * @private 
     */
    constructor() {
        
    }

    /**
     * @returns Config
     */
    static getInstance(){
        if (!Config.#instance) {
            Config.#instance= new Config()
        }

        return Config.#instance ;
    }

   param = (key,idMeta="sonata-config") => {
    if (typeof key !== "string") { return null; }

    if (this.#params === null) {
        const raw = getMetaContent(idMeta);
        if (!raw) {
            Logger.warn(`[SEO] meta ${idMeta} introuvable ou vide.`);
            this.#params = {}; // fallback vide
        } else {
            try {
                this.#params = JSON.parse(raw);
                Logger.log('[SEO] Config chargée:', this.#params);
            } catch (e) {
                Logger.error(`[SEO] Erreur parsing JSON dans la meta ${idMeta}:`, e);
                this.#params = {}; // fallback vide
            }
        }
    }

    return key in this.#params ? this.#params[key] : null;
}

}

export const config=Config.getInstance();

 /**
     * 
     * @param {boolean} DEBUG 
     * @param {string} APP_ENV 
     * @returns 
     */
export function disableUserInteractions(APP_ENV = 'prod', DEBUG = false) {
    if (DEBUG === true && APP_ENV === "dev") { return; }

      jQuery(document).on('contextmenu', function(e) {
            e.preventDefault(); // Empêche le comportement par défaut du clic droit
      });
    
        jQuery(document).on('keydown', function(e) {
            // e.which est l'équivalent de e.keyCode en jQuery, mieux supporté sur les anciens navigateurs
            if (e.which === 123 || // F12
                (e.ctrlKey && e.shiftKey && e.which === 73) || // Ctrl+Shift+I
                (e.ctrlKey && e.shiftKey && e.which === 74) || // Ctrl+Shift+J
                (e.ctrlKey && e.which === 85) // Ctrl+U
            ) {
                e.preventDefault();
            }
        });
}

export function select2(subject=document) {
    const selects = subject.querySelectorAll('select:not([data-sonata-select2="false"])');
    selects.forEach((element) => {
      // Éviter la double initialisation si Sonata rappelle la fonction
      if (element.tomselect) return;

      let allowClearEnabled = false;
      let maximumSelectionLength = 1;
      let allowTags = false;

      // Logique d'extraction des attributs conforme à ta capture Sonata
      if (
          element.querySelector('option[value=""]') ||
          (element.getAttribute('data-placeholder') && element.getAttribute('data-placeholder').length) ||
          element.getAttribute('data-sonata-select2-allow-clear') === 'true'
      ) {
          allowClearEnabled = true;
      }

      if (element.getAttribute('data-sonata-select2-allow-tags') === 'true') {
          allowTags = true;
      }

      if (element.getAttribute('data-sonata-select2-maximumSelectionLength')) {
          maximumSelectionLength = parseInt(element.getAttribute('data-sonata-select2-maximumSelectionLength'));
      }

      // Initialisation de Tom Select
      const tomSlecte=new TomSelect(element, {
          plugins:  ['dropdown_input'],
          create: allowTags,
          maxItems: maximumSelectionLength,
          maxOptions:null,
          placeholder: element.getAttribute('data-placeholder') || (allowClearEnabled ? ' ' : ''),
          allowEmptyOption: allowClearEnabled,
          // On imite le comportement 'bootstrap-5'
          searchField: ['text'], 
                shouldOpen: true, // Ouvre le dropdown dès le clic
                
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results">Aucun résultat pour "' + escape(data.input) + '"</div>';
                    }
                },
          onInitialize: function() {
              // On retire form-control comme dans la fonction originale
              this.control.classList.remove('form-control');
          },
          hideSelected: true,
        onDropdownOpen: function(dropdown) {
        dropdown.style.opacity = "0";
        dropdown.style.transform = "translateY(-10px)";
        dropdown.style.transition = "all 0.3s cubic-bezier(0.19, 1, 0.22, 1)";
        
        requestAnimationFrame(() => {
            dropdown.style.opacity = "1";
            dropdown.style.transform = "translateY(0)";
        });
      },
      onDropdownClose: function(dropdown) {
        dropdown.style.opacity = "0";
        dropdown.style.transform = "translateY(-10px)";
    }
      });
    });
  }


/**
 * Gère le clic sur le bouton 'toggle account' et affiche la modale de confirmation.
 * 
 *  * Gère l'action de renvoi d'email de vérification via SweetAlert2.
 * * Ce script est conçu pour écouter les clics sur les boutons ayant l'attribut 
 * data-action-confirm, ce qui déclenche la modale de confirmation.
 * 
 * * @fileoverview Gestionnaire d'événements pour la régénération de mot de passe temporaire via AJAX/PATCH.
 * Nécessite jQuery et le composant de gestion de flash messages de votre application (si utilisé).
 */
export function crudAccountHandle() {
    jQuery(document).on(
        'click', 
        `a.btn-toggle-account,
        a.btn-resend-email-verification,
        a.js-regenerate-password-btn,
        a.js-toggle-visible-btn`,
        async (event) => {
        event.preventDefault();
        event.stopPropagation();
            const currentTarget = event.currentTarget;
            
         try {
            await CRUDActionConfirmationHandle({
            element:currentTarget ,
             eventName:jQuery(currentTarget).attr('data-event-name'),
             confirmDialogConfig: {
                icon: 'question',
                background: '#00427E',
                color: '#fff',
                position:"top",
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary me-2'
                },
                reverseButtons: true,
                confirmButtonText: await SonataTranslator.trans('LABEL_BTN_CONFIRM','sonata-translations'),
                cancelButtonText: await SonataTranslator.trans('LABEL_BTN_CANCEL','sonata-translations'),
                showClass: {
                    popup: 'animate__animated animate__bounceIn'
                }
            }, 
             cancelDialogConfig: {
                icon: 'info',
                title: await SonataTranslator.trans('ACTION_CANCELLED_SUCCESS','sonata-translations'),
                 timer: 45000,
                position: 'top',
                showConfirmButton: false,
                showCloseButton: true,
            }
        });
         } catch (error) {
             console.error(error);
         }
    });
};

export function crudUserAccountListener() {
    jQuery(document).on(
        `resend:email:verificatio:password:confirmed 
         create:action:generate:password:confirmed
         account:toggle:confirmed
         account:toggle-visible-team-member
         `,
        async (event) =>{
        event.preventDefault();
        event.stopPropagation();
            const detail = event.detail;
            let headers = {
                 'X-Requested-With': 'XMLHttpRequest',
                 'Accept'          : 'application/json',
            }

        const csrf = detail.sourceElement.dataset.csrf ?? null;
            if (csrf) {
                headers['X-CSRF-TOKEN'] = csrf;
            }

            try {
            const { title, message }= actionTranslator(detail.element);
            const response = await processCRUDAction({
                eventDetail: detail ,
                httpMethod: detail.httpMethodRequestAction,
                retryCount: 2,
                optionsHeaders:headers,
                loadingConfig: {
                    background: '#00427E',
                    color: '#fff',
                    title: await SonataTranslator.trans(title, 'sonata-translations'),
                    text: await SonataTranslator.trans(message, 'sonata-translations')
                },
                translator: (key, error, lang) => {
                    if (error) {
                        return fetchErrorTranslator.translate(error.name, error, lang);
                    }
                    return translations[key] || key; 
                },
            });
            Logger.log(response)
        } catch (error) {
            Logger.log(error);
        }
    })
}

/**
 * @param {HTMLElement} element
 * @return {
 *    title: string,
 *     message: string  
 * }
 */
function actionTranslator(element) {
    const target = jQuery(element);
    return {
        title: target.attr("data-loading-config-key-title") ?? "FORM_SUBMISSION_PROGRESS_TITLE" ,
        message:target.attr("data-loading-config-key-message") ?? "FORM_SUBMISSION_PROGRESS_MESSAGE" 
    }
}
