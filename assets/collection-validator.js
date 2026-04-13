// assets/admin/collection-validator.js

import jQuery from 'jquery';
import {
    FormValidateController
} from '@wlindabla/form_validator';

/**
 * Gère la validation des nouveaux champs ajoutés dynamiquement
 * dans les collections Symfony/Sonata
 */
export class CollectionValidator {

  /**
   * @param {FormValidateController} formValidateController
   * @param {Function} addHashToIds - ta fonction utilitaire
   * @param {CustomeEvent} FieldValidationFailed - ton event custom
   */
  constructor(formValidateController, addHashToIds, FieldValidationFailed) {
    this.formValidator       = formValidateController;
    this.addHashToIds        = addHashToIds;
    this.FieldValidationFailed = FieldValidationFailed;
    this.__form              = formValidateController.form;

    this.bindCollectionEvents();
    this.bindMutationObserver();
  }

  // ─── 1. Via les events jQuery de Sonata ──────────────────────────────────

  bindCollectionEvents() {
    // Déclenché APRÈS qu'un élément a été ajouté dans la collection
    jQuery(document).on('sonata-collection-item-added', (event) => {
      const newRow = event.target;
      console.log('[CollectionValidator] Nouvel élément ajouté:', newRow);
      this.bindNewRow(jQuery(newRow));
    });

    // Déclenché AVANT suppression — nettoyage si nécessaire
    jQuery(document).on('sonata-collection-item-deleted', (event) => {
      console.log('[CollectionValidator] Élément supprimé:', event.target);
    });

    // Déclenché APRÈS suppression réussie
    jQuery(document).on('sonata-collection-item-deleted-successful', (event) => {
      console.log('[CollectionValidator] Suppression confirmée:', event.target);
    });

    // Ancien event Sonata (compatibilité)
    jQuery(document).on('sonata-admin-append-form-element', (event) => {
      const newRow = event.target;
      this.bindNewRow(jQuery(newRow));
    });
  }

  // ─── 2. Via MutationObserver (fallback + sécurité) ───────────────────────

  bindMutationObserver() {
    const formElement = this.__form[0] ?? document.querySelector('form.form-validate');
    if (!formElement) return;

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          // On s'intéresse uniquement aux éléments HTML (pas les textes)
          if (!(node instanceof HTMLElement)) return;

          // Vérifier si c'est une ligne de collection Sonata
          // Sonata ajoute des div avec data-index ou des li dans les collections
          if (this.isCollectionRow(node)) {
            console.log('[MutationObserver] Nouvelle ligne collection détectée:', node);
            this.bindNewRow(jQuery(node));
          }
        });
      });
    });

    observer.observe(formElement, {
      childList: true,   // Observer les ajouts/suppressions d'enfants directs
      subtree:   true,   // Observer tout l'arbre sous le form
    });

    this.observer = observer;
  }

  /**
   * Vérifie si un noeud est une ligne de collection Sonata/Symfony
   */
  isCollectionRow(node) {
    return (
      node.matches?.('[data-index]')           // Symfony collection row
      || node.matches?.('.sonata-collection-row')
      || node.matches?.('[id*="___name___"]')   // Prototype Symfony non remplacé
      || node.closest?.('[data-prototype]') !== null
      // Vérifie si le noeud contient des inputs de formulaire
      || node.querySelector?.('input, select, textarea') !== null
    );
  }

  // ─── 3. Binding des événements sur le nouveau row ─────────────────────────

  bindNewRow($newRow) {
    // Extraire les nouveaux champs du row ajouté
    const newInputs    = $newRow.find('input, textarea, select');
    if (newInputs.length === 0) return;

    // Reconstruire les IDs pour le nouveau row
    const newIds = [];
    newInputs.each((_, el) => {
      if (el.id) newIds.push(el.id);
    });

    if (newIds.length === 0) return;

    console.log('[CollectionValidator] Binding validation sur:', newIds);

    const idsBlur      = newIds.map(id => `#${id}`).join(',');
    const idsInput     = newIds
      .filter(id => {
        const el = document.getElementById(id);
        return el && el.type !== 'file';
      })
      .map(id => `#${id}`).join(',');

    const idsChange    = newIds
      .filter(id => {
        const el = document.getElementById(id);
        return el && el.type === 'file';
      })
      .map(id => `#${id}`).join(',');

    const idsDragenter = idsChange; // Même logique pour dragenter

    // ── Blur → validation ─────────────────────────────────────────
    if (idsBlur) {
      this.__form.on('blur', idsBlur, async (event) => {
        const target = event.target;
        if (
          (target instanceof HTMLInputElement ||
           target instanceof HTMLTextAreaElement)
          && target.type !== 'file'
        ) {
          await this.formValidator.validateChildrenForm(target);
        }
      });
    }

    this.__form.on(this.FieldValidationFailed, (event) => {
        const data = (event.originalEvent).detail;

        this.formValidator.addErrorMessageChildrenForm(
            jQuery(data.targetChildrenForm),
            data.message,
            'container-div-error-message');
    });

    // ── Input → clear error ───────────────────────────────────────
    if (idsInput) {
      this.__form.on('input', idsInput, (event) => {
        const target = event.target;
        if (
          (target instanceof HTMLInputElement ||
           target instanceof HTMLTextAreaElement)
          && target.type !== 'file'
        ) {
          this.formValidator.clearErrorDataChildren(target);
        }
      });
    }

    // ── Change → validation fichiers ──────────────────────────────
    if (idsChange) {
      this.__form.on('change', idsChange, async (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.type === 'file') {
          await this.formValidator.validateChildrenForm(target);
        }
      });
    }

    // ── Dragenter → clear error fichiers ─────────────────────────
    if (idsDragenter) {
      this.__form.on('dragenter', idsDragenter, (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.type === 'file') {
          this.formValidator.clearErrorDataChildren(target);
        }
      });
    }

    // Émettre un event custom pour tes autres modules
    document.dispatchEvent(new CustomEvent('spa:collection:row:added', {
      detail: { row: $newRow[0], ids: newIds }
    }));
  }

  // ─── 4. Nettoyage ────────────────────────────────────────────────────────

  destroy() {
    this.observer?.disconnect();
    jQuery(document).off('sonata-collection-item-added');
    jQuery(document).off('sonata-collection-item-deleted');
    jQuery(document).off('sonata-collection-item-deleted-successful');
    jQuery(document).off('sonata-admin-append-form-element');
  }
}