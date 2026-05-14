import './styles/contact.css';
import { select2 } from "./utils";
/**
 * contact.js — Vanilla JS
 * Animations & interactions formulaire ProTIC Contact
 * - Barre de progression dynamique
 * - Validation visuelle temps réel (is-valid / is-invalid)
 * - Compteur de caractères textarea
 * - Drag & Drop zone upload
 * - Spinner submit
 * - Shake animation sur erreur
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ── Sélecteurs ── */
    const form        = document.getElementById('pc-manuscript-form');
    const progressBar = document.getElementById('pc-progress-bar');
    const uploadZone  = document.getElementById('pc-upload-zone');
    const fileInput   = document.querySelector('input[type="file"]');
    const uploadInner = document.getElementById('pc-upload-inner');
    const preview     = document.getElementById('pc-upload-preview');
    const fileName    = document.getElementById('pc-file-name');
    const fileSize    = document.getElementById('pc-file-size');
    const fileRemove  = document.getElementById('pc-file-remove');

    if (!form) return;
     select2(document);
   
    const requiredFields = form.querySelectorAll(
        '.pc-input[required], .pc-select[required], .pc-textarea[required], ' +
        'input[required], select[required], textarea[required]'
    );

    const updateProgress = () => {
        if (!progressBar || !requiredFields.length) return;
        let filled = 0;
        requiredFields.forEach(f => {
            if (f.value && f.value.trim() !== '' && f.value !== f.getAttribute('placeholder')) {
                filled++;
            }
        });
        const pct = Math.round((filled / requiredFields.length) * 100);
        progressBar.style.width = pct + '%';
        // Couleur finale quand complet
        progressBar.style.background = pct === 100
            ? 'linear-gradient(90deg, #34d399, #059669)'
            : 'linear-gradient(90deg, #1a3a8f, #FFD700)';
    };

    requiredFields.forEach(f => {
        f.addEventListener('input', updateProgress);
        f.addEventListener('change', updateProgress);
    });

   const formatSize = bytes => {
        if (bytes < 1024)       return bytes + ' o';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / (1024 * 1024)).toFixed(2) + ' Mo';
    };

    const showPreview = file => {
        if (!preview || !uploadInner || !fileName || !fileSize) return;
        fileName.textContent = file.name;
        fileSize.textContent = formatSize(file.size);
        uploadInner.style.display = 'none';
        preview.style.display = 'flex';
        // Animation entrée
        preview.style.opacity = '0';
        preview.style.transform = 'translateY(8px)';
        requestAnimationFrame(() => {
            preview.style.transition = 'opacity .3s, transform .3s';
            preview.style.opacity = '1';
            preview.style.transform = 'translateY(0)';
        });
    };

    const clearPreview = () => {
        if (!preview || !uploadInner || !fileInput) return;
        preview.style.display = 'none';
        uploadInner.style.display = '';
        fileInput.value = '';
        updateProgress();
        fileInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    if (fileInput) {
        fileInput.addEventListener('change', e => {
            const f = e.target.files?.[0];
            if (f) { showPreview(f); updateProgress(); }
        });
    }
    if (fileRemove) {
        fileRemove.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            clearPreview();
        });
    }
 
    if (uploadZone) {
       ['dragenter', 'dragover'].forEach(ev =>
            uploadZone.addEventListener(ev, e => {
                e.preventDefault();
                uploadZone.classList.add('is-dragover');
            })
        );

        uploadZone.addEventListener('dragleave', () => 
            uploadZone.classList.remove('is-dragover')
        );

        uploadZone.addEventListener('drop', e => {
            e.preventDefault();
            uploadZone.classList.remove('is-dragover');
            const f = e.dataTransfer?.files?.[0];
            if (f && fileInput) {
                const dt = new DataTransfer();
                dt.items.add(f);
                fileInput.files = dt.files;
                showPreview(f);
                updateProgress();
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },true);
}

    /* Shake si erreurs Symfony présentes au chargement */
    const hasErrors = form?.querySelectorAll('.pc-field-error li').length > 0;
    if (hasErrors) shakeForm();

    function shakeForm() {
        const card = document.getElementById('pc-form-card');
        if (!card) return;
        card.style.animation = 'none';
        card.offsetHeight; // reflow
        card.style.animation = 'pcShake .4s ease';
        setTimeout(() => { card.style.animation = ''; }, 420);
    }

    /* Injecte @keyframes pcShake une seule fois */
    if (!document.getElementById('pc-shake-style')) {
        const s = document.createElement('style');
        s.id = 'pc-shake-style';
        s.textContent = `
            @keyframes pcShake {
                0%,100%{ transform: translateX(0); }
                20%    { transform: translateX(-6px); }
                40%    { transform: translateX(6px); }
                60%    { transform: translateX(-4px); }
                80%    { transform: translateX(4px); }
            }
        `;
        document.head.appendChild(s);
    }

    form.querySelectorAll('.pc-input, .pc-textarea, .pc-select').forEach(input => {
        const wrap = input.closest('.pc-field');
        if (!wrap) return;
        input.addEventListener('focus', () => wrap.classList.add('is-focused'));
        input.addEventListener('blur',  () => wrap.classList.remove('is-focused'));
    });

    form.querySelectorAll('.pc-input-wrap').forEach(wrap => {
        const icon = wrap.querySelector('.pc-input-wrap__icon');
        if (!icon) return;
        const input = wrap.querySelector('input, select');
        if (!input) return;
        input.addEventListener('focus', () => {
            icon.style.transform = 'translateY(-50%) scale(1.15)';
            icon.style.color = '#FFD700';
        });
        input.addEventListener('blur', () => {
            icon.style.transform = 'translateY(-50%) scale(1)';
            icon.style.color = input.value ? '#FFD700' : 'rgba(255,255,255,.35)';
        });
        // Pré-remplissage initial (rechargement avec erreurs)
        if (input.value) {
            icon.style.color = '#FFD700';
        }
    });

    // Initialisation compteur progression
    updateProgress();
});