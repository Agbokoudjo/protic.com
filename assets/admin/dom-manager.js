export class DomManager {

  /**
   * Point d'entrée — appelé après chaque swap SPA
   * @param {HTMLElement} container
   */
  static reinitialize(container) {
    // Ordre important !
    DomManager.reExecuteScripts(container);
    DomManager.reconnectStimulusOutlets(container);
    DomManager.reinitializeDateControllers(container);
    DomManager.reinitializeBootstrapDropdowns(container);
    DomManager.reinitializePaginationLinks(container);
    DomManager.reinitializeSortingLinks(container);
    DomManager.reinitializeBatchCheckbox(container);

    // Émettre un event pour tes propres modules
    document.dispatchEvent(new CustomEvent('spa:dom:ready', {
      detail: { container }
    }));
  }

  // ─── 1. Ré-exécution des <script> inline injectés ─────────────────────────

  static reExecuteScripts(container) {
    container.querySelectorAll('script').forEach(oldScript => {
      // Scripts externes déjà présents dans le <head> → ignorer
      if (oldScript.src && document.querySelector(`script[src="${oldScript.src}"]`)) {
        oldScript.remove();
        return;
      }

      const newScript = document.createElement('script');
      [...oldScript.attributes].forEach(attr =>
        newScript.setAttribute(attr.name, attr.value)
      );
      if (!oldScript.src) {
        newScript.textContent = oldScript.textContent;
      }
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  // ─── 2. Reconnexion des Stimulus controllers via window.sonataApplication ──

  /**
   * Stimulus observe le DOM via MutationObserver donc les nouveaux éléments
   * avec data-controller sont détectés automatiquement après innerHTML.
   *
   * Le VRAI problème : les outlets Sonata utilisent des sélecteurs avec
   * l'uniqid qui change à chaque rendu serveur.
   * Ex: data-sonata-filter-list-sonata-filter-outlet="#filter-container-s69db38c053269"
   * → après swap, le nouvel ID est différent → outlet cassé.
   *
   * Solution : resynchroniser les outlets APRÈS le swap.
   */
  static reconnectStimulusOutlets(container) {
    const app = window.sonataApplication;
    if (!app) {
      console.warn('[DomManager] window.sonataApplication non trouvé');
      return;
    }

    // Récupérer les nouveaux IDs après le swap
    const filterContainer = container.querySelector('[data-controller~="sonata-filter"]');
    const filterList      = document.querySelector('[data-controller~="sonata-filter-list"]');

    // filterList peut être dans #app-content-header (pas dans container si container = #app-content)
    // donc on cherche aussi dans tout le #app-main
    const appMain = document.getElementById('app-main');

    if (filterContainer && filterList) {
      const newContainerId = filterContainer.id;
      const newListId      = filterList.id;

      // Mettre à jour les outlets croisés avec les nouveaux IDs
      filterContainer.setAttribute(
        'data-sonata-filter-sonata-filter-list-outlet',
        `#${newListId}`
      );
      filterList.setAttribute(
        'data-sonata-filter-list-sonata-filter-outlet',
        `#${newContainerId}`
      );

      // Forcer Stimulus à reconnecter les outlets en "touchant" l'attribut
      DomManager.forceReconnect(filterContainer, 'sonata-filter');
      DomManager.forceReconnect(filterList, 'sonata-filter-list');
    }

    // Reconnecter tous les autres controllers dans le container
    container.querySelectorAll('[data-controller]').forEach(el => {
      el.getAttribute('data-controller')
        .split(/\s+/)
        .filter(Boolean)
        .forEach(name => {
          // "date" et "sonata-filter" ont leur traitement dédié
          if (name === 'date') return;
          DomManager.forceReconnect(el, name);
        });
    });
  }

  /**
   * Force Stimulus à déconnecter et reconnecter un controller sur un élément.
   * Technique : modifier temporairement data-controller via requestAnimationFrame.
   */
  static forceReconnect(element, controllerName) {
    const current = element.getAttribute('data-controller') || '';
    const others  = current.split(/\s+/).filter(c => c !== controllerName && c !== '');

    // Step 1 — retirer le controller → Stimulus appelle disconnect()
    if (others.length === 0) {
      element.removeAttribute('data-controller');
    } else {
      element.setAttribute('data-controller', others.join(' '));
    }

    // Step 2 — remettre au prochain frame → Stimulus appelle connect()
    requestAnimationFrame(() => {
      const restored = [...others, controllerName].join(' ').trim();
      element.setAttribute('data-controller', restored);
    });
  }

  // ─── 3. Controller "date" de Sonata ──────────────────────────────────────
  // Le controller Stimulus "date" est enregistré sous "sonata-date"
  // Il formate les <time> avec Intl.DateTimeFormat
  // On le gère manuellement car c'est plus fiable que la reconnexion Stimulus

  static reinitializeDateControllers(container) {
    container.querySelectorAll('time[data-controller="date"]').forEach(el => {
      const dateValue = el.getAttribute('data-date-date-value');
      const locale    = el.getAttribute('data-date-locale-value') || 'fr';

      if (!dateValue) return;

      try {
        const date      = new Date(dateValue);
        const formatted = new Intl.DateTimeFormat(locale, {
          day:    '2-digit',
          month:  '2-digit',
          year:   'numeric',
          hour:   '2-digit',
          minute: '2-digit',
        }).format(date);

        el.textContent = formatted;

        // Stimulus ne doit plus toucher cet élément → retirer data-controller
        // pour éviter double formatage
        el.removeAttribute('data-controller');
        el.setAttribute('data-date-formatted', 'true');

      } catch (err) {
        console.warn('[DomManager] Erreur date:', dateValue, err);
      }
    });
  }

  // ─── 4. Bootstrap Dropdowns ──────────────────────────────────────────────
  // Bootstrap 5 est importé via Vite → window.bootstrap disponible
  // Les dropdowns dans le nouveau contenu ont besoin d'être réinitialisés

  static reinitializeBootstrapDropdowns(container) {
    if (!window.bootstrap?.Dropdown) return;

    container.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(el => {
      // Détruire l'instance existante
      window.bootstrap.Dropdown.getInstance(el)?.dispose();
      // Recréer
      new window.bootstrap.Dropdown(el);
    });
  }

  // ─── 5. Liens de pagination → passer par le router SPA ──────────────────

  static reinitializePaginationLinks(container) {
    const pagination = container.querySelector('#pagination-container');
    if (!pagination) return;

    pagination.querySelectorAll('a[href]').forEach(link => {
      // Éviter double binding
      if (link.dataset.spabound) return;
      link.dataset.spabound = 'true';

      link.addEventListener('click', async (e) => {
        e.preventDefault();
       await  window.appRouter?.navigate(link.getAttribute('href'));
      });
    });
  }

  // ─── 6. Liens de tri des colonnes → passer par le router SPA ─────────────

  static reinitializeSortingLinks(container) {
    container.querySelectorAll('.sonata-ba-list-field-header a[href]').forEach(link => {
      if (link.dataset.spabound) return;
      link.dataset.spabound = 'true';

      link.addEventListener('click', async(e) => {
        e.preventDefault();
        await window.appRouter?.navigate(link.getAttribute('href'));
      });
    });
  }

  // ─── 7. Checkbox batch "tout sélectionner" ───────────────────────────────

  static reinitializeBatchCheckbox(container) {
    const master = container.querySelector('#list_batch_checkbox');
    if (!master) return;

    if (master.dataset.spabound) return;
    master.dataset.spabound = 'true';

    master.addEventListener('change', (e) => {
      container
        .querySelectorAll('input[type="checkbox"][name="idx[]"]')
        .forEach(cb => { cb.checked = e.target.checked; });
    });
  }
}