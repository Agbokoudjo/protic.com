import './styles/login.css';
import { disableUserInteractions } from './utils';
import jQuery from 'jquery';
window.jQuery = jQuery;
window.$ = jQuery;
//import '@fortawesome/fontawesome-free/css/all.min.css';
document.addEventListener('DOMContentLoaded', () => {
    disableUserInteractions("prod","false");

// ── Toggle afficher/masquer mot de passe ─────────────────────
const toggleBtn = document.getElementById('togglePassword');
const pwInput   = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');

if (toggleBtn && pwInput && eyeIcon) {
    toggleBtn.addEventListener('click', () => {
        const isHidden = pwInput.type === 'password';
        pwInput.type   = isHidden ? 'text' : 'password';
        eyeIcon.classList.toggle('fa-eye',      !isHidden);
        eyeIcon.classList.toggle('fa-eye-slash', isHidden);
    });
}

// ── Animation entrée de la card ──────────────────────────────
const card = document.querySelector('.login-card');
if (card) {
    card.style.opacity   = '0';
    card.style.transform = 'translateY(24px)';
    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    requestAnimationFrame(() => {
        setTimeout(() => {
            card.style.opacity   = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
}

// ── Focus automatique sur le champ username ──────────────────
const usernameInput = document.getElementById('email');
if (usernameInput && !usernameInput.value) {
    usernameInput.focus();
}

});