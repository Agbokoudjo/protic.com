'use strict';

/**
     * Initialise la logique de toggle pour les champs "Membre de l'équipe".
     *
     * La checkbox `.js-is-member-toggle` pilote la visibilité de
     * `.js-member-field` et de leurs wrappers `.form-group` parents.
     *
     * Si data-member-fields-open="true" (édition d'un utilisateur déjà membre),
     * les champs sont affichés dès le chargement.
 */
function initMemberToggle() {
    const checkbox = document.querySelector('.js-is-member-toggle');
    if (!checkbox) return;

    // Récupère l'état initial depuis l'attribut data- (posé par le PHP)
    const initiallyOpen = checkbox.dataset.memberFieldsOpen === 'true';

    // Tous les wrappers .form-group qui contiennent un champ js-member-field
    const memberWrappers = Array.from(
        document.querySelectorAll('.js-member-field')
    ).map(function (field) {
        // Remonte jusqu'au .form-group parent
        return field.closest('.form-group') || field.closest('.sonata-ba-field');
    }).filter(Boolean);

    // Appliquer l'état initial
    toggleMemberFields(memberWrappers,initiallyOpen);

    // Écouter les changements de la checkbox
    checkbox.addEventListener('change', function () {
        toggleMemberFields(memberWrappers,this.checked);

        // Si on décoche, vider les champs pour éviter d'envoyer des données
        // orphelines (le subscriber Doctrine les ignore de toute façon,
        // mais on garde le formulaire propre côté UX).
        if (!this.checked) {
            document.querySelectorAll('.js-member-field').forEach(function (field) {
                if (field.type === 'checkbox') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
            });
        }
    });
}

/**
 * Affiche ou masque les champs complémentaires.
 * @param {Array} memberWrappers
 * @param {boolean} show
 */
function toggleMemberFields(memberWrappers,show) {
    memberWrappers.forEach(function (wrapper) {
        wrapper.style.display = show ? '' : 'none';
    });
}


    // SECTION 2 — Affichage/masquage du mot de passe (œil/slash)
    //
    // Pour tout input[data-password-toggle="true"], on injecte un bouton
    // bascule qui permet de voir/masquer le mot de passe saisi.
    // =====================================================================
    function initPasswordToggle() {
        document.querySelectorAll('input[data-password-toggle="true"]').forEach(function (input) {
            // Conteneur parent relatif pour positionner le bouton
            const wrapper = input.closest('.input-group') || input.parentElement;
            wrapper.style.position = 'relative';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Afficher / masquer le mot de passe');
            btn.setAttribute('tabindex', '-1');
            btn.className = 'btn btn-link btn-sm password-toggle-btn';
            btn.style.cssText = [
                'position: absolute',
                'right: 10px',
                'top: 50%',
                'transform: translateY(-50%)',
                'padding: 0',
                'border: none',
                'background: transparent',
                'color: #aaa',
                'cursor: pointer',
                'z-index: 5',
            ].join(';');

            // Icône SVG œil (visible)
            const iconEye = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>`;

            // Icône SVG œil barré (masqué)
            const iconEyeOff = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
                         a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1
                         12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07
                         a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>`;

            btn.innerHTML = iconEye;
            wrapper.appendChild(btn);

            // Ajuster le padding-right de l'input pour ne pas masquer le texte
            input.style.paddingRight = '38px';

            let visible = false;
            btn.addEventListener('click', function () {
                visible = !visible;
                input.type    = visible ? 'text' : 'password';
                btn.innerHTML = visible ? iconEyeOff : iconEye;
            });
        });
    }

    // =====================================================================
    // SECTION 3 — Indicateur de force du mot de passe
    //
    // Affiché sous le premier champ de type "Nouveau mot de passe"
    // =====================================================================
    function initPasswordStrength() {
        // Premier champ "plainPassword" (first)
        const pwInput = document.querySelector(
            '[data-type="password"]'
        );
        if (!pwInput) return;

        const bar = document.createElement('div');
        bar.className = 'password-strength-bar';
        bar.style.cssText = 'height:4px;border-radius:2px;margin-top:6px;transition:width .3s,background .3s;width:0;';

        const label = document.createElement('small');
        label.className = 'password-strength-label text-muted';
        label.style.display = 'block';

        pwInput.insertAdjacentElement('afterend', label);
        pwInput.insertAdjacentElement('afterend', bar);

        const levels = [
            { min: 0,  color: '#e74c3c', width: '20%', text: 'Très faible' },
            { min: 1,  color: '#e67e22', width: '40%', text: 'Faible'      },
            { min: 2,  color: '#f1c40f', width: '60%', text: 'Moyen'       },
            { min: 3,  color: '#2ecc71', width: '80%', text: 'Fort'        },
            { min: 4,  color: '#27ae60', width: '100%', text: 'Très fort'  },
        ];

        /**
         * Score simple (0-4) sans dépendance externe.
         * @param {string} pw
         * @returns {number}
         */
        function score(pw) {
            if (!pw) return -1;
            let s = 0;
            if (pw.length >= 8)  s++;
            if (pw.length >= 12) s++;
            if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
            if (/[0-9]/.test(pw)) s++;
            if (/[^A-Za-z0-9]/.test(pw)) s++;
            return Math.min(s, 4);
        }

        pwInput.addEventListener('input', function () {
            const s = score(this.value);
            if (s < 0) {
                bar.style.width = '0';
                label.textContent = '';
                return;
            }
            const lvl = levels[s];
            bar.style.width      = lvl.width;
            bar.style.background = lvl.color;
            label.textContent    = lvl.text;
            label.style.color    = lvl.color;
        });
    }

initMemberToggle();
initPasswordToggle();
initPasswordStrength();