/**
 * mobile_guard.js
 *
 * Blocks access to the SonataAdmin dashboard on viewports narrower than 992px.
 * Displays a full-screen overlay with a logout button that redirects to Symfony's
 * /logout route. The overlay is injected into the DOM on DOMContentLoaded and
 * re-evaluated on every resize event (debounced).
 *
 * Usage (in your Sonata layout Twig template, before </body>):
 *
 *   <script
 *     src="{{ asset('js/mobile_guard.js') }}"
 *     data-logout-url="{{ path('app_logout') }}"
 *   ></script>
 *
 * If you don't use the data attribute, set LOGOUT_URL below or pass it via a
 * global letiable defined before this script loads:
 *   window.MOBILE_GUARD_LOGOUT_URL = '/logout';
 */

(function () {
  'use strict';

  /* -----------------------------------------------------------------------
   * Configuration
   * --------------------------------------------------------------------- */
  let BREAKPOINT   = 992;   // px — minimum width that allows dashboard access
  let OVERLAY_ID   = 'mobile-guard-overlay';
  let DEBOUNCE_MS  = 150;   // resize debounce delay

  /* Resolve the logout URL from (priority order):
   * 1. data-logout-url attribute on the <script> tag itself
   * 2. window.MOBILE_GUARD_LOGOUT_URL global
   * 3. Hard-coded fallback '/logout' (Symfony's default security route)       */
  let LOGOUT_URL = (function () {
    let me = document.querySelector('script#sonata_user_admin_security_logout');
    return (me && me.getAttribute('data-logout-url'))
      || window.MOBILE_GUARD_LOGOUT_URL
      || '/admin/logout';
  }());

  /* -----------------------------------------------------------------------
   * Build the overlay element (created once, then shown/hidden)
   * --------------------------------------------------------------------- */
  function buildOverlay() {
    let el = document.createElement('div');
    el.id = OVERLAY_ID;

    /* ---- Inline styles (no external CSS dependency) ------------------- */
    let overlayStyles = [
      'position:fixed',
      'inset:0',
      'z-index:999999',
      'display:flex',
      'align-items:center',
      'justify-content:center',
      'background:#0f1117',
      'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif',
      'padding:1.5rem'
    ].join(';');

    el.setAttribute('style', overlayStyles);
    el.setAttribute('role', 'dialog');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-labelledby', 'mg-title');
    el.setAttribute('aria-describedby', 'mg-desc');

    /* ---- Card ---------------------------------------------------------- */
    el.innerHTML = [
      '<div style="',
        'background:#1a1d27;',
        'border:1px solid rgba(255,255,255,0.08);',
        'border-radius:20px;',
        'padding:2.5rem 2rem 2rem;',
        'max-width:360px;',
        'width:100%;',
        'text-align:center;',
        'box-sizing:border-box',
      '">',

        /* Icon circle */
        '<div style="',
          'width:72px;height:72px;',
          'border-radius:50%;',
          'background:rgba(83,74,183,0.15);',
          'border:1px solid rgba(83,74,183,0.3);',
          'display:flex;align-items:center;justify-content:center;',
          'margin:0 auto 1.5rem',
        '">',
          /* Simple desktop-off SVG icon (no external dependency) */
          '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" ',
              'stroke="#7f77dd" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" ',
              'aria-hidden="true">',
            '<rect x="2" y="3" width="20" height="14" rx="2"/>',
            '<line x1="8" y1="21" x2="16" y2="21"/>',
            '<line x1="12" y1="17" x2="12" y2="21"/>',
            '<line x1="2" y1="2" x2="22" y2="22" stroke="#e24b4a" stroke-width="1.8"/>',
          '</svg>',
        '</div>',

        /* Badge */
        '<div style="',
          'display:inline-block;',
          'font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;',
          'padding:4px 12px;border-radius:20px;',
          'background:rgba(162,45,45,0.2);color:#f09595;',
          'border:1px solid rgba(162,45,45,0.3);',
          'margin-bottom:1.25rem',
        '">Accès restreint</div>',

        /* Title */
        '<h1 id="mg-title" style="',
          'font-size:20px;font-weight:700;color:#f0eeff;',
          'margin:0 0 .75rem;letter-spacing:-.015em;line-height:1.3',
        '">Interface non disponible</h1>',

        /* Description */
        '<p id="mg-desc" style="',
          'font-size:14px;color:#7e8299;line-height:1.65;margin:0 0 1.5rem',
        '">',
          'Ce tableau de bord est conçu exclusivement pour les appareils ',
          'd\'une largeur d\'écran minimale de&nbsp;',
          '<strong style="color:#a9b0c8">992&thinsp;px</strong>.',
          '<br>Votre appareil actuel ne répond pas à cette exigence.',
        '</p>',

        /* Width indicator */
        '<div id="mg-width-info" style="',
          'display:inline-flex;align-items:center;gap:8px;',
          'background:rgba(255,255,255,0.04);',
          'border:1px solid rgba(255,255,255,0.08);',
          'border-radius:10px;padding:7px 16px;',
          'font-size:13px;color:#a9b0c8;',
          'margin-bottom:1.75rem',
        '">',
          '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" ',
              'stroke="currentColor" stroke-width="2" stroke-linecap="round" ',
              'stroke-linejoin="round" aria-hidden="true">',
            '<path d="M21 12H3M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4"/>',
          '</svg>',
          'Largeur détectée\u00a0: ',
          '<strong id="mg-width-value" style="color:#7f77dd">—</strong>',
        '</div>',

        /* Divider */
        '<hr style="border:none;border-top:1px solid rgba(255,255,255,0.06);margin:0 0 1.5rem">',

        /* Logout button */
        '<button id="mg-logout-btn" type="button" style="',
          'width:100%;padding:13px;border-radius:12px;border:none;cursor:pointer;',
          'background:#534ab7;color:#fff;',
          'font-size:14px;font-weight:700;letter-spacing:.01em;',
          'display:flex;align-items:center;justify-content:center;gap:10px;',
          'transition:background .18s ease',
        '">',
          /* Logout arrow SVG */
          '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" ',
              'stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ',
              'aria-hidden="true">',
            '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>',
            '<polyline points="16 17 21 12 16 7"/>',
            '<line x1="21" y1="12" x2="9" y2="12"/>',
          '</svg>',
          'Se déconnecter',
        '</button>',

        /* Hint */
        '<p style="font-size:12px;color:#4e5268;line-height:1.55;margin:1rem 0 0">',
          'Vous serez redirigé vers la page de connexion.',
        '</p>',

      '</div>'
    ].join('');

    return el;
  }

  /* -----------------------------------------------------------------------
   * Overlay mount / unmount helpers
   * --------------------------------------------------------------------- */
  function mountOverlay() {
    if (document.getElementById(OVERLAY_ID)) return; // already mounted

    let overlay = buildOverlay();

    /* Bind logout button */
    let btn = overlay.querySelector('#mg-logout-btn');
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.style.opacity = '0.6';
      btn.textContent = 'Déconnexion en cours…';
      /* Symfony handles session invalidation server-side via the /logout route */
      window.location.href = LOGOUT_URL;
    });

    /* Hover effect on logout button */
    btn.addEventListener('mouseenter', function () {
      btn.style.background = '#3c3489';
    });
    btn.addEventListener('mouseleave', function () {
      btn.style.background = '#534ab7';
    });

    document.body.appendChild(overlay);
    updateWidthDisplay();

    /* Prevent body scroll while overlay is visible */
    document.body.style.overflow = 'hidden';
  }

  function unmountOverlay() {
    let existing = document.getElementById(OVERLAY_ID);
    if (existing) {
      existing.parentNode.removeChild(existing);
      document.body.style.overflow = '';
    }
  }

  /* Updates the "Largeur détectée" badge inside the overlay */
  function updateWidthDisplay() {
    let el = document.getElementById('mg-width-value');
    if (el) el.textContent = window.innerWidth + ' px';
  }

  /* -----------------------------------------------------------------------
   * Core guard logic
   * --------------------------------------------------------------------- */
  function check() {
    if (window.innerWidth < BREAKPOINT) {
      mountOverlay();
      updateWidthDisplay();
    } else {
      unmountOverlay();
    }
  }

  /* Debounce helper */
  function debounce(fn, delay) {
    let timer;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, delay);
    };
  }

  /* -----------------------------------------------------------------------
   * Initialisation
   * --------------------------------------------------------------------- */
  function init() {
    check(); // immediate check on load
    window.addEventListener('resize', debounce(check, DEBOUNCE_MS));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init(); // DOM already ready (script placed at end of body)
  }

}());
