import { startStimulusApp,registerControllers  } from "vite-plugin-symfony/stimulus/helpers"
import * as stimulus from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import jQuery from 'jquery';
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
import {basicLightboxImage,basicLightboxDocument} from  './basicLightbox.js';

window.$ = jQuery;
window.jQuery = jQuery;
window.stimulus = stimulus;
const sonataApplication = startStimulusApp();
window.sonataApplication = sonataApplication;
sonataApplication.debug = true;

import {
    appTranslation,
    fetchErrorTranslator
} from '@wlindabla/form_validator';
import { config,select2 } from "./utils.js";

import.meta.glob('./images/**/*', { eager: true });

    registerControllers(
    sonataApplication,
    import.meta.glob(
        "./controllers/*_controller.js",
        {
        // pensez à ajouter le suffixe "?stimulus"
        //query: "?stimulus",

        // les imports dynamiques et les `Lazy-`contrôleurs sont gérés en interne, dans tous les
        // cas il faut spécifier eager à true pour éviter des imbrications de promesses.
        eager: true,
        },
    ),
    );

    const sonataControllers = import.meta.glob(
    "../vendor/sonata-project/admin-bundle/assets/js/controllers/*_controller.[jt]s",
        { eager: true });
    Object.entries(sonataControllers).forEach(([filePath, module]) => {
    // Extraire le nom du controller depuis le chemin
    // Ex: ".../filter_list_controller.js" → "filter-list"
    const match = filePath.match(/([^/]+)_controller\.[jt]sx?$/);
    if (!match) return;

    const name       = match[1].replace(/_/g, "-");
    const identifier = `sonata-${name}`;
    const controller = module.default;

    if (controller) {
        sonataApplication.register(identifier, controller);
    }
});
console.log("🚀 Stimulus est prêt et les contrôleurs sont chargés :",sonataApplication)

jQuery(async function init() {
    window.SonataTranslator = appTranslation;
     // Récupère le hash actuel des traductions
    const currentHash = jQuery('meta[name="sonata-translations-hash"]').attr('content');
    const cachedHash = localStorage.getItem('sonata_translations_hash');
    
    // Si le hash a changé, vide le cache
    if (currentHash && cachedHash !== currentHash) {
        Admin.log('Translation keys changed - clearing cache');
        await appTranslation.clearCache();
        localStorage.setItem('sonata_translations_hash', currentHash);
    }

    await appTranslation.preload('sonata-translations');
  window.fetchErrorTranslator = fetchErrorTranslator 
  basicLightboxImage();
  basicLightboxDocument();
})


/**
 * Remplacement de SonataCore pour les Flashmessages
 * Suppression de la dépendance jQuery et iCheck
 */
const SonataCoreModern = {
  // Remplace remove_iCheck_in_flashmessage
  cleanFlashMessages() {
    // Au lieu de 'destroy' via iCheck, on s'assure juste que 
    // les éléments ont un comportement de checkbox standard.
    const elements = document.querySelectorAll('.read-more-state');
    elements.forEach(el => {
      // Si iCheck avait ajouté des wrappers div ou des classes, 
      // on pourrait les nettoyer ici, mais souvent le simple fait 
      // de ne pas appeler iCheck suffit.
      el.style.display = 'inline-block'; // Force l'affichage si masqué par un vieux CSS
    });
  },

  addFlashmessageListener() {
    const states = document.querySelectorAll('.read-more-state');
    
    states.forEach((element) => {
      element.addEventListener('change', (event) => {
        // Recherche du label associé via l'attribut 'for'
        const label = document.querySelector(`label[for="${element.id}"]`);
        if (!label) return;

        const labelMore = label.querySelector('.more');
        const labelLess = label.querySelector('.less');

        if (!labelMore || !labelLess) return;

        if (event.target.checked) {
          labelMore.classList.add('hide');
          labelLess.classList.remove('hide');
        } else {
          labelMore.classList.remove('hide');
          labelLess.classList.add('hide');
        }
      });
    });
  }
};

// Initialisation sans jQuery
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    SonataCoreModern.cleanFlashMessages();
    SonataCoreModern.addFlashmessageListener();
  });
} else {
  // Le DOM est déjà prêt
  SonataCoreModern.cleanFlashMessages();
  SonataCoreModern.addFlashmessageListener();
}

class Translation {
  messages = null;

  static trans(key) {
      try {
        return appTranslation.trans(key)
      } catch (e) {
        throw new Error(
          `An error has occurred resolving the "sonata-translations" meta tag: ${e.message}.`
        );
      }
  }
}

export const Admin = {
  /**
   * This function must be called when an ajax call is done, to ensure
   * the retrieved html is properly setup
   *
   * @param subject
   */
  shared_setup(subject) {
    Admin.log('[core|shared_setup] Register services on', subject);
    Admin.setup_select2(subject);
    //Admin.setup_xeditable(subject);
    Admin.setup_inline_form_errors(subject);
   // Admin.setup_tree_view(subject);
  },
  get_config(key) {
    return config.param(key);
  },
  get_translations(key) {
    return Translation.trans(key);
  },
  setup_list_modal(modal) {
    Admin.log('[core|setup_list_modal] configure modal on', modal);
    // this will force relation modal to open list of entity in a wider modal
    // to improve readability
    jQuery('div.modal-dialog', modal).css({
      width: '90%', // choose your width
      height: '85%',
      padding: 0,
    });
    jQuery('div.modal-content', modal).css({
      'border-radius': '0',
      height: '100%',
      padding: 0,
    });
    jQuery('.modal-body', modal).css({
      width: 'auto',
      height: '90%',
      padding: 15,
      overflow: 'auto',
    });

    jQuery(modal).trigger('sonata-admin-setup-list-modal');
  },
  setup_select2(subject=document) {
    if (!Admin.get_config('USE_SELECT2')) { return; }
    
    Admin.log('[core|setup_select2] configure Select2 on', subject);
    select2(subject);
  },
  setup_icheck(subject) {
    if (Admin.get_config('USE_ICHECK')) {
      Admin.log('[core|setup_icheck] configure iCheck on', subject);

      const inputs = jQuery(
        'input[type="checkbox"]:not(label.btn > input, [data-sonata-icheck="false"]), input[type="radio"]:not(label.btn > input, [data-sonata-icheck="false"])',
        subject
      );
      inputs.iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
      });

      // In case some checkboxes were already checked (for instance after moving
      // back in the browser's session history) update iCheck checkboxes.
      if (subject === window.document) {
        setTimeout(() => {
          inputs.iCheck('update');
        }, 0);
      }
    }
  },
  /**
   * Setup checkbox range selection
   *
   * Clicking on a first checkbox then another with shift + click
   * will check / uncheck all checkboxes between them
   *
   * @param {string|Object} subject The html selector or object on which function should be applied
   */
  setup_checkbox_range_selection(subject) {
    Admin.log(
      '[core|setup_checkbox_range_selection] configure checkbox range selection on',
      subject
    );

    let previousIndex;
    const useICheck = Admin.get_config('USE_ICHECK');

    // When a checkbox or an iCheck helper is clicked
    jQuery('tbody input[type="checkbox"], tbody .iCheck-helper', subject).on('click', (event) => {
      let input;

      if (useICheck) {
        input = jQuery(event.target).prev('input[type="checkbox"]');
      } else {
        input = jQuery(event.target);
      }

      if (input.length) {
        const currentIndex = input.closest('tr').index();

        if (event.shiftKey && previousIndex >= 0) {
          const isChecked = jQuery(
            `tbody input[type="checkbox"]:nth(${currentIndex})`,
            subject
          ).prop('checked');

          // Check all checkbox between previous and current one clicked
          jQuery('tbody input[type="checkbox"]', subject).each((index, element) => {
            if (
              (index > previousIndex && index < currentIndex) ||
              (indexedDB > currentIndex && index < previousIndex)
            ) {
              if (useICheck) {
                jQuery(element).iCheck(isChecked ? 'check' : 'uncheck');

                return;
              }

              jQuery(element).prop('checked', isChecked);
            }
          });
        }

        previousIndex = currentIndex;
      }
    });
  },

  setup_xeditable(subject) {
    Admin.log('[core|setup_xeditable] configure xeditable on', subject);
    jQuery('.x-editable', subject).editable({
      emptyclass: 'editable-empty btn btn-sm btn-default',
      emptytext: '<i class="fas fa-pencil-alt"></i>',
      container: 'body',
      placement: 'auto',
      success(response) {
        const html = jQuery(response);
        Admin.setup_xeditable(html);
        jQuery(this).closest('td').replaceWith(html);
      },
      error: (xhr) => {
        // On some error responses, we return JSON.
        if (xhr.getResponseHeader('Content-Type') === 'application/json') {
          return JSON.parse(xhr.responseText);
        }

        return xhr.responseText;
      },
    });
  },

  /**
   * render log message
   * @param mixed
   */
  log(...args) {
    if (!Admin.get_config('DEBUG')) {
      return;
    }

    const msg = `[Sonata.Admin] ${Array.prototype.join.call(args, ', ')}`;
    if (window.console && window.console.log) {
      window.console.log(msg);
    } else if (window.opera && window.opera.postError) {
      window.opera.postError(msg);
    }
  },

  setup_inline_form_errors(subject) {
    Admin.log('[core|setup_inline_form_errors] show first tab with errors', subject);

    const deleteCheckboxSelector = '.sonata-ba-field-inline-table [id$="_delete"][type="checkbox"]';

    jQuery(deleteCheckboxSelector, subject).each((index, element) => {
      Admin.switch_inline_form_errors(jQuery(element));
    });

    jQuery(subject).on('change', deleteCheckboxSelector, (event) => {
      Admin.switch_inline_form_errors(jQuery(event.target));
    });
  },

  /**
   * Disable inline form errors when the row is marked for deletion
   */
  switch_inline_form_errors(subject) {
    Admin.log('[core|switch_inline_form_errors] switch_inline_form_errors', subject);

    const row = subject.closest('.sonata-ba-field-inline-table');
    const errors = row.find('.sonata-ba-field-error-messages');
    if (subject.is(':checked')) {
      row.find('[required]').removeAttr('required').attr('data-required', 'required');
      errors.hide();
    } else {
      row.find('[data-required]').attr('required', 'required');
      errors.show();
    }
  },

  setup_tree_view(subject) {
    Admin.log('[core|setup_tree_view] setup tree view', subject);

    //jQuery('ul.js-treeview', subject).treeView();
  },

  /** Return the width for simple and sortable select2 element * */
  get_select2_width(element) {
    const ereg = /width:(auto|(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc)))/i;

    // this code is an adaptation of select2 code (initContainerWidth function)
    let style = element.attr('style');
    // console.log("main style", style);

    if (style !== undefined) {
      const attrs = style.split(';');

      for (let i = 0, l = attrs.length; i < l; i += 1) {
        const matches = attrs[i].replace(/\s/g, '').match(ereg);
        if (matches !== null && matches.length >= 1) return matches[1];
      }
    }

    style = element.css('width');
    if (style.indexOf('%') > 0) {
      return style;
    }

    return '100%';
  },

  setup_sortable_select2(subject, data, customOptions) {
    const idLookupTable = [];
    const selectedItems = [];
    const unselectedItems = [];
    const selectedIds = subject.val() ? subject.val().split(',') : [];

    for (let i = 0; i < data.length; i += 1) {
      if (idLookupTable[data[i].label]) {
        Admin.log(
          '[setup_sortable_select2] error: sortable requires all option labels to be unique'
        );
      }
      idLookupTable[data[i].label] = data[i].data;

      const item = {
        id: data[i].data,
        text: data[i].label,
      };

      const selectedIndex = selectedIds.indexOf(data[i].data);
      if (selectedIndex !== -1) {
        selectedItems[selectedIndex] = item;
      } else {
        unselectedItems.push(item);
      }
    }

    const options = {
       theme: "bootstrap-5",
      width: () => Admin.get_select2_width(subject),
      dropdownAutoWidth: true,
      data: [...selectedItems, ...unselectedItems],
      multiple: true,
      ...customOptions,
    };
    subject.select2(options);
    const list = subject.data('select2').$container.find('ul.select2-selection__rendered');
    list.sortable({
      containment: 'parent',
      items: '> li[data-select2-id]',
      update: () => {
        // Find all values in the new order and put them in the input.
        const sortedIds = list
          .children('li[data-select2-id]')
          .toArray()
          .map((li) => idLookupTable[li.title]);
        subject.val(sortedIds.join());
      },
    }); // On form submit, transform value to match what is expected by server

    subject.parents('form:first').submit(() => {
      let values = subject.val().trim();

      if (values !== '') {
        let baseName = subject.attr('name');
        values = values.split(',');
        baseName = baseName.substring(0, baseName.length - 1);

        for (let i = 0; i < values.length; i += 1) {
          jQuery('<input>')
            .attr('type', 'hidden')
            .attr('name', ''.concat(baseName + i, ']'))
            .val(values[i])
            .appendTo(subject.parents('form:first'));
        }
      }

      subject.remove();
    });
  },
  setup_sticky_elements() {
    // eslint-disable-next-line no-console
    console.warn('The "Admin.setup_sticky_elements()" method is deprecated and will be removed.');
  },
  setup_readmore_elements() {
    // eslint-disable-next-line no-console
    console.warn('The "Admin.setup_readmore_elements()" method is deprecated and will be removed.');
  },
};

window.Admin = Admin;

jQuery(() => {
  jQuery('html').removeClass('no-js');

  Admin.shared_setup(document);
});

jQuery(() => {
  jQuery('select.per-page').one('change.select2', (event) => {
    event.target.dispatchEvent(new Event('change'));
  });
});

jQuery(() => {
  jQuery('.sidebar-toggle').on('click', () => {
    if (document.cookie.includes('sonata_sidebar_hide=1')) {
      document.cookie = 'sonata_sidebar_hide=0;path=/';

      return;
    }

    document.cookie = 'sonata_sidebar_hide=1;path=/';
  });
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
// Lancement automatique au chargement du DOM
document.addEventListener('DOMContentLoaded', localDatetime);
