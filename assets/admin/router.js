// router.js
import Swal from 'sweetalert2';
export class SpaRouter {

  constructor() {
    this.mainContainer  = document.getElementById('app-main');
    this.contentHeader  = document.getElementById('app-content-header');
    this.contentArea    = document.getElementById('app-content');
    this.isNavigating   = false;

    this.bindSidebarLinks();
    this.bindShowLinks();
    this.bindDeleteLinks();
    this.handlePopState();
  }

  // ─── Fetch du fragment Ajax ───────────────────────────────────────────────

  async fetchFragment(url) {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html',
      },
      credentials: 'same-origin',
    });

    if (!response.ok) throw new Error(`HTTP ${response.status} : ${url}`);
    return await response.text();
  }

  async fetchFullPage(url) {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'text/html'
      },
      credentials: 'same-origin', // Important pour la session Symfony
    });

    if (!response.ok) throw new Error(`HTTP ${response.status} : ${url}`);
    
    return await response.text();
  }
  // ─── Navigation principale ────────────────────────────────────────────────

    async navigate(url) {
    if (this.isNavigating) return;
    this.isNavigating = true;
    this.setLoading(true);
    this.setActiveLink(url);

    try {
      // Décider selon l'URL DE DESTINATION (pas l'URL actuelle)
      const fullHtml = this.needsFullPage(window.location.href) || this.isShowUrl(url)
        ? await this.fetchFullPage(url)
        : await this.fetchFragment(url);
     
      const parser     = new DOMParser();
      const virtualDoc = parser.parseFromString(fullHtml, 'text/html');

      // Détecter ce que Sonata a renvoyé
      if (virtualDoc.getElementById('app-main')) {
        // Page complète reçue → extraire #app-main
        this.mainContainer.innerHTML = virtualDoc.getElementById('app-main').innerHTML;
        // Mettre à jour les références
        this.contentArea   = document.getElementById('app-content');
        this.contentHeader = document.getElementById('app-content-header');
      } else {
        // Fragment Ajax → swap chirurgical
        this.swapContent(virtualDoc);
      }

      window.history.pushState({ url }, '', url);
      this.dispatchNavigated(url);

    } catch (err) {
      console.error('[SPA] Erreur navigate:', err);
      window.location.href = url;
    } finally {
      this.setLoading(false);
      this.isNavigating = false;
    }
  }

  needsFullPage(currentUrl) {
    return (
      /\/dashboard/.test(currentUrl)       // dashboard
    );
  }

  isShowUrl(url) {
  // Pattern Sonata : /admin/{resource}/{token}/show
  return /\/[^/]+\/show(\?.*)?$/.test(url);
  }

  isServerManagedUrl(url) {
    return (
      /\/edit(\?.*)?$/.test(url)    // page edit
      || /\/create(\?.*)?$/.test(url) // page create
      || /\/batch(\?.*)?$/.test(url)  // batch actions
    );
}
  // ─── Remplacement chirurgical ─────────────────────────────────────────────

  swapContent(virtualDoc) {

    // ① _list_filters_actions → dans la navbar du header
    this.swapFilterActions(virtualDoc);

    // ② _list_filters → la boîte de filtres
    this.swapFilters(virtualDoc);

    // ③ _list_table → le tableau de données
    this.swapListTable(virtualDoc);

    // ④ Cas : contenu simple (form, show, dashboard...)
    this.swapGenericContent(virtualDoc);

    this.swapSonataActions(virtualDoc);
  }

  swapSonataActions(virtualDoc) {
    const navbarHeader = this.contentHeader?.querySelector('.navbar-header .navbar-brand');
    if (navbarHeader) {
       navbarHeader.innerHTML =''
    }

    const newSonataActions = virtualDoc.querySelector(
      'ul[id^="container-sonata-actions"]'
    );
    const currentSonataActions = this.contentHeader?.querySelector(
     'ul[id^="container-sonata-actions"]'
    );
    console.log(newSonataActions , currentSonataActions)
    if (newSonataActions && currentSonataActions) {
      currentSonataActions.replaceWith(newSonataActions);
    } else if (newSonataActions && !currentSonataActions) {
      // Ajouter dans la navbar si pas encore présent
      const navbarRight = this.contentHeader?.querySelector('.navbar-nav');
      navbarRight?.appendChild(newSonataActions);
    } else if (!newSonataActions && currentSonataActions) {
      // Supprimer si la nouvelle page n'a pas de bouttons d'actions crud
      currentSonataActions.remove();
    }
   }
  
  // ① Filter actions (bouton Filtres dans la navbar)
  swapFilterActions(virtualDoc) {
    const newFilterActions = virtualDoc.querySelector(
      'ul[id^="filter-list-"], .sonata-filter-container'
    );
    const currentFilterActions = this.contentHeader?.querySelector(
      'ul[id^="filter-list-"], .sonata-filter-container'
    );

    if (newFilterActions && currentFilterActions) {
      currentFilterActions.replaceWith(newFilterActions);
    } else if (newFilterActions && !currentFilterActions) {
      // Ajouter dans la navbar si pas encore présent
      const navbarRight = this.contentHeader?.querySelector('.navbar-nav');
      navbarRight?.appendChild(newFilterActions);
    } else if (!newFilterActions && currentFilterActions) {
      // Supprimer si la nouvelle page n'a pas de filtres
      currentFilterActions.remove();
    }
  }

  // ② Filtres (la boîte de recherche)
  swapFilters(virtualDoc) {
    const newFilters = virtualDoc.querySelector('.sonata-filters-box');
    const currentFilters = this.contentArea?.querySelector('.sonata-filters-box');

    if (newFilters && currentFilters) {
      currentFilters.replaceWith(newFilters);
    } else if (newFilters && !currentFilters) {
      // Insérer avant le tableau
      const listTable = this.contentArea?.querySelector('.sonata-ba-list');
      if (listTable) {
        listTable.parentNode.insertBefore(newFilters, listTable);
    } else {
        this.contentArea?.appendChild(newFilters);
    }
    } else if (!newFilters && currentFilters) {
      currentFilters.remove();
    }
  }

  bindDeleteLinks() {
    this.mainContainer.addEventListener('click', (e) => {
      const link = e.target.closest('a.delete_link');
      if (!link) return;

      e.preventDefault();
      e.stopPropagation();

      const deleteUrl = link.getAttribute('href');
      this.confirmDelete(deleteUrl);
    });
  }
  
  bindShowLinks() {
    this.mainContainer.addEventListener('click', (e) => {
      const link = e.target.closest([
        'a.view_link',                    // lien "Afficher" dans le tableau liste
        'a.btn-show',                     // bouton "Afficher" dans la page edit
        'a.sonata-action-element.btn-show' // variante Sonata
      ].join(','));

      if (!link) return;
      if (this.shouldIgnoreLink(link)) return;

      e.preventDefault();
      this.navigate(link.getAttribute('href'));
    });
  }
  
  // ③ Tableau de données
  swapListTable(virtualDoc) {
    // Le tableau est dans .col-xs-12.col-md-12 qui contient .sonata-ba-list
    const newListRow = virtualDoc.querySelector('.col-xs-12.col-md-12:has(.sonata-ba-list), .col-md-12:has(form[action*="batch"])');
    const currentListRow = this.contentArea?.querySelector('.col-xs-12.col-md-12:has(.sonata-ba-list), .col-md-12:has(form[action*="batch"])');

    if (newListRow && currentListRow) {
      currentListRow.replaceWith(newListRow);
    } else if (newListRow && !currentListRow) {
      this.contentArea?.appendChild(newListRow);
    } else if (!newListRow && currentListRow) {
      currentListRow.remove();
    }
  }

  // ④ Contenu générique (form edit/create, show, dashboard)
  swapGenericContent(virtualDoc) {
    const genericSelectors = [
      '.sonata-ba-form',
      '.sonata-ba-show',
      '.sonata-ba-content',
      '.sonata-ba-preview',
    ];

    genericSelectors.forEach(selector => {
      const newEl  = virtualDoc.querySelector(selector);
      const currEl = this.contentArea?.querySelector(selector);

      if (newEl && currEl)       currEl.replaceWith(newEl);
      else if (newEl && !currEl) this.contentArea?.appendChild(newEl);
      else if (!newEl && currEl) currEl.remove();
    });
  }

  // ─── Sidebar links ────────────────────────────────────────────────────────

  bindSidebarLinks() {
    const sidebar = document.querySelector('#sonata-admin-sidebar');
    if (!sidebar) return;

    sidebar.addEventListener('click', async (e) => {
      const link = e.target.closest('a[href]');
      if (!link || this.shouldIgnoreLink(link)) return;
      e.preventDefault();
      await this.navigate(link.getAttribute('href'));
    });

    const listAction = document.querySelector('.content-header a.list-action');

    if (!listAction) { return; }

    listAction.addEventListener('click', async (e) => {
      const link = e.target;
      e.preventDefault();
      await this.navigate(link.getAttribute('href'));
    });

  }

  shouldIgnoreLink(link) {
    const href = link.getAttribute('href');
    if (!href) return true;
    if (href.startsWith('#')) return true;
    if (href.startsWith('javascript')) return true;
    if (link.getAttribute('target') === '_blank') return true;
    if (href.startsWith('http') && !href.includes(window.location.hostname)) return true;
    if (this.isServerManagedUrl(href)) return true;

    return false;
  }

  // ─── Active link ──────────────────────────────────────────────────────────

  setActiveLink(url) {
    const sidebar = document.querySelector('#sonata-admin-sidebar');
    if (!sidebar) return;
    sidebar.querySelectorAll('a.active, li.active').forEach(el => el.classList.remove('active'));
    const active = sidebar.querySelector(`a[href="${url}"]`);
    if (active) {
      active.classList.add('active');
      active.closest('li')?.classList.add('active');
    }
  }

  // ─── Loading ──────────────────────────────────────────────────────────────

  setLoading(state) {
    if (!this.contentArea) return;
    this.contentArea.style.opacity       = state ? '0.4' : '1';
    this.contentArea.style.pointerEvents = state ? 'none' : '';
    this.contentHeader.style.opacity     = state ? '0.4' : '1';
  }

  // ─── Events ───────────────────────────────────────────────────────────────

  dispatchNavigated(url) {
    document.dispatchEvent(new CustomEvent('spa:navigated', {
      detail: { url, main: this.mainContainer, content: this.contentArea }
    }));
  }

  handlePopState() {
    window.addEventListener('popstate', async (e) => {
      await this.navigate(e.state?.url || window.location.pathname);
    });
  }

  async confirmDelete(deleteUrl) {
  // Étape 1 — Récupérer le token CSRF depuis la page de confirmation Sonata
  // On fetch la page delete en GET pour extraire le token
  const {csrfToken,title,message,btnDeleteText} = await this.fetchDeleteCsrfToken(deleteUrl);

  if (!csrfToken) {
    console.error('[SPA] CSRF token introuvable pour:', deleteUrl);
    // Fallback → laisser le navigateur gérer
    window.location.href = deleteUrl;
    return;
  }

  // Étape 2 — Afficher la confirmation SweetAlert2
  const result = await Swal.fire({
    title: title || 'Confirmer la suppression',
    text: message ||  'Cette action est irréversible. Voulez-vous vraiment supprimer cet élément ?',
    icon:  'warning',
    showCancelButton:  true,
    confirmButtonColor: '#d33',
    cancelButtonColor:  '#6c757d',
    confirmButtonText:  `${btnDeleteText ?? 'Supprimer'}`,
    cancelButtonText:   '<i class="fas fa-times"></i> Annuler',
    reverseButtons: true,
    focusCancel:    true,
  });

  if (!result.isConfirmed) return;

  // Étape 3 — Envoyer la requête DELETE
  await this.executeDelete(deleteUrl, csrfToken);
}

  async fetchDeleteCsrfToken(deleteUrl) {
    try {
      // Fetch la page de confirmation en GET
      // Sonata renvoie le token dans un input hidden
      const html = await this.fetchFullPage(deleteUrl);

      const parser = new DOMParser();
      const doc    = parser.parseFromString(html, 'text/html');

      const tokenInput = doc.querySelector('.sonata-ba-delete input[name="_sonata_csrf_token"]');
      return {
        csrfToken: tokenInput ? tokenInput.value : null,
        title: doc.querySelector('.sonata-ba-delete .box-header .box-title')?.innerText ?? null,
        message: doc.querySelector('.sonata-ba-delete .box-body')?.innerText ?? null,
        btnDeleteText: doc.querySelector('.sonata-ba-delete button[type="submit"]')?.innerText ?? null
      };

    } catch (err) {
      console.error('[SPA] Erreur fetch CSRF token:', err);
      return null;
    }
  }

  async executeDelete(deleteUrl, csrfToken) {
  // Afficher un loader pendant la suppression
    Swal.fire({
      title: 'Suppression en cours...',
      allowOutsideClick: false,
      allowEscapeKey:    false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      const formData = new FormData();
      formData.append('_method', 'DELETE');
      formData.append('_sonata_csrf_token', csrfToken);
      formData.append('btn_delete', '1');

      const response = await fetch(deleteUrl, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body:        formData,
        credentials: 'same-origin',
      });

      const data = await response.json();

      if (data.result === 'ok') {
        await Swal.fire({
          title: 'Supprimé !',
          text:  'L\'élément a été supprimé avec succès.',
          icon:  'success',
          timer: 2000,
          showConfirmButton: false,
        });

        // Retourner vers la liste
        const listUrl = this.extractListUrl(deleteUrl);
        await this.navigate(listUrl);

    } else {
      // ❌ Erreur
      await Swal.fire({
        title: 'Erreur !',
        text:  'Une erreur est survenue lors de la suppression.',
        icon:  'error',
        confirmButtonText: 'OK',
      });
    }

  } catch (err) {
    console.error('[SPA] Erreur executeDelete:', err);
    await Swal.fire({
      title: 'Erreur !',
      text:  'Une erreur réseau est survenue.',
      icon:  'error',
      confirmButtonText: 'OK',
    });
  }
}
  extractListUrl(deleteUrl) {
    return deleteUrl.replace(/\/[^/]+\/delete(\?.*)?$/, '/list');
  }
}