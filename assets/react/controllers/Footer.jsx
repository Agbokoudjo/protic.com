import React  from 'react';

/* ── Icônes SVG inline ── */
const Icon = ({ name, size = 16 }) => {
    const paths = {
        pin    : <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>,
        phone  : <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 011 1V20a1 1 0 01-1 1C9.61 21 3 14.39 3 6a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1.02l-2.2 2.2z"/>,
        mail   : <path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>,
        shield : <path d="M12 2L4 5v6c0 5.25 3.5 10.15 8 11.5C16.5 21.15 20 16.25 20 11V5l-8-3z"/>,
        arrow  : <path d="M5 12h14M12 5l7 7-7 7"/>,
        fb     : <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>,
        twitter: <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>,
        linkedin:<><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></>,
        whatsapp:<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>,
    };
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" width={size} height={size} aria-hidden="true" style={{ flexShrink: 0 }}>
            {paths[name]}
        </svg>
    );
};

/* ── Lien nav footer ── */
const FooterNavLink = ({ href, label }) => (
    <li>
        <a href={href} style={{
            display       : 'flex',
            alignItems    : 'center',
            gap           : '10px',
            color         : 'rgba(255,255,255,0.60)',
            textDecoration: 'none',
            fontSize      : '0.875rem',
            padding       : '5px 0',
            transition    : 'color 0.22s, gap 0.22s',
        }}
        onMouseEnter={e => { e.currentTarget.style.color = '#fff'; e.currentTarget.style.gap = '14px'; }}
        onMouseLeave={e => { e.currentTarget.style.color = 'rgba(255,255,255,0.60)'; e.currentTarget.style.gap = '10px'; }}
        >
            <span style={{ color: '#FFD700', display: 'flex' }}>
                <Icon name="arrow" size={13} />
            </span>
            {label}
        </a>
    </li>
);

/* ── Titre de colonne avec séparateur doré ── */
const ColTitle = ({ children }) => (
    <div style={{ marginBottom: '24px' }}>
        <h4 style={{
            color      : '#fff',
            fontSize   : '0.95rem',
            fontWeight : 700,
            margin     : '0 0 12px',
            letterSpacing: '0.02em',
        }}>
            {children}
        </h4>
        <div style={{
            height    : '2px',
            width     : '40px',
            background: 'linear-gradient(90deg, #FFD700, rgba(255,215,0,0.2))',
            borderRadius: '2px',
        }} />
    </div>
);

/* ── Icône réseau social ── */
const SocialBtn = ({ name, href, label }) => (
    <a href={href}
       aria-label={label}
       target="_blank"
       rel="noopener noreferrer"
       style={{
           width          : '38px',
           height         : '38px',
           borderRadius   : '8px',
           border         : '1px solid rgba(255,255,255,0.15)',
           background     : 'rgba(255,255,255,0.07)',
           display        : 'flex',
           alignItems     : 'center',
           justifyContent : 'center',
           color          : 'rgba(255,255,255,0.55)',
           textDecoration : 'none',
           transition     : 'background 0.22s, color 0.22s, border-color 0.22s, transform 0.22s',
       }}
       onMouseEnter={e => {
           e.currentTarget.style.background    = 'rgba(255,215,0,0.15)';
           e.currentTarget.style.color         = '#FFD700';
           e.currentTarget.style.borderColor   = 'rgba(255,215,0,0.4)';
           e.currentTarget.style.transform     = 'translateY(-3px)';
       }}
       onMouseLeave={e => {
           e.currentTarget.style.background    = 'rgba(255,255,255,0.07)';
           e.currentTarget.style.color         = 'rgba(255,255,255,0.55)';
           e.currentTarget.style.borderColor   = 'rgba(255,255,255,0.15)';
           e.currentTarget.style.transform     = 'translateY(0)';
       }}
    >
        <Icon name={name} size={16} />
    </a>
);

export default function Footer({ config }) {
    if (!config) return null;

    const {
        contact = { addresses: [], telephones: [], emails: [] },
        legal   = { rccm: '', ifu: '', cnss: '', compte: '' },
        membre  = '',
        LOGO_SRC = '/assets/images/logo_footer.png',
    } = config;

    const yr = new Date().getFullYear();

    /* Styles inline (évite un fichier CSS externe supplémentaire) */
    const S = {
        footer: {
            background  : 'linear-gradient(180deg, #060f2a 0%, #03091a 100%)',
            borderTop   : '1px solid rgba(255,255,255,0.10)',
            color       : 'rgba(255,255,255,0.65)',
            fontFamily  : "'Segoe UI', system-ui, sans-serif",
           
        },
        topBar: {
            borderBottom: '1px solid rgba(255,255,255,0.07)',
            padding     : '16px 0',
            display     : 'flex',
            alignItems  : 'center',
            justifyContent: 'space-between',
            flexWrap    : 'wrap',
            gap         : '12px',
        },
        topBarText: {
            fontSize    : '0.78rem',
            color       : 'rgba(255,255,255,0.35)',
            letterSpacing: '0.06em',
            textTransform: 'uppercase',
        },
        grid: {
            display              : 'grid',
            gridTemplateColumns  : '2fr 1fr 1.6fr 1.4fr',
            gap                  : '48px',
            padding              : '56px 0 48px',
        },
        logo: {
            maxWidth   : '170px',
            height     : 'auto',
            marginBottom: '18px',
            display    : 'block',
        },
        tagline: {
            fontSize   : '0.85rem',
            lineHeight : 1.75,
            marginBottom: '18px',
            color      : 'rgba(255,255,255,0.55)',
        },
        memberBadge: {
            display        : 'inline-flex',
            alignItems     : 'center',
            gap            : '8px',
            fontSize       : '0.75rem',
            fontWeight     : 600,
            color          : '#FFD700',
            background     : 'rgba(255,215,0,0.10)',
            border         : '1px solid rgba(255,215,0,0.28)',
            borderRadius   : '20px',
            padding        : '6px 14px',
            marginBottom   : '20px',
        },
        socialRow: {
            display    : 'flex',
            gap        : '8px',
            marginTop  : '4px',
        },
        navList: {
            listStyle  : 'none',
            margin     : 0,
            padding    : 0,
        },
        contactItem: {
            display    : 'flex',
            alignItems : 'flex-start',
            gap        : '10px',
            marginBottom: '10px',
            fontSize   : '0.84rem',
            lineHeight : 1.55,
        },
        contactIcon: {
            color      : '#FFD700',
            marginTop  : '2px',
            flexShrink : 0,
        },
        contactLink: {
            color          : 'rgba(255,255,255,0.60)',
            textDecoration : 'none',
            transition     : 'color 0.2s',
        },
        legalList: {
            listStyle  : 'none',
            margin     : 0,
            padding    : 0,
        },
        legalItem: {
            display        : 'flex',
            flexDirection  : 'column',
            marginBottom   : '14px',
            paddingBottom  : '14px',
            borderBottom   : '1px solid rgba(255,255,255,0.06)',
        },
        legalLabel: {
            fontSize   : '0.7rem',
            color      : 'rgba(255,255,255,0.35)',
            letterSpacing: '0.06em',
            textTransform: 'uppercase',
            marginBottom: '3px',
        },
        legalValue: {
            fontSize   : '0.82rem',
            fontWeight : 600,
            color      : 'rgba(255,255,255,0.75)',
            fontFamily : 'monospace',
        },
        divider: {
            height     : '1px',
            background : 'linear-gradient(90deg, transparent, rgba(255,255,255,0.08) 20%, rgba(255,215,0,0.15) 50%, rgba(255,255,255,0.08) 80%, transparent)',
        },
        bottom: {
            display        : 'flex',
            alignItems     : 'center',
            justifyContent : 'space-between',
            flexWrap       : 'wrap',
            gap            : '12px',
            padding        : '20px 0',
            fontSize       : '0.78rem',
            color          : 'rgba(255,255,255,0.30)',
        },
        bottomStrong: {
            color      : 'rgba(255,255,255,0.60)',
            fontWeight : 600,
        },
        bottomDot: {
            display    : 'inline-block',
            width      : '4px',
            height     : '4px',
            borderRadius: '50%',
            background : '#FFD700',
            margin     : '0 10px',
            verticalAlign: 'middle',
        },
    };

    return (
        <footer style={S.footer}>

            {/* ── Barre supérieure : slogan + réseaux ── */}
            <div className="container " style={S.topBar}>
                <span style={S.topBarText}>
                    📖 Votre partenaire éditorial au Bénin &amp; en Afrique de l'Ouest
                </span>
                <div style={S.socialRow}>
                    <SocialBtn name="fb"       href="#" label="Facebook ProTIC" />
                    <SocialBtn name="whatsapp" href="#" label="WhatsApp ProTIC" />
                    <SocialBtn name="linkedin" href="#" label="LinkedIn ProTIC" />
                    <SocialBtn name="twitter"  href="#" label="X (Twitter) ProTIC" />
                </div>
            </div>

            {/* ── Grille principale ── */}
            <div className="container protic-footer__grid-responsive" style={S.grid}>

                {/* Col 1 — Brand */}
                <div data-aos="fade-up" data-aos-delay="0">
                    <img src={LOGO_SRC} alt="ProTIC Editions & Services" style={S.logo} width="170" height="57" />

                    <p style={S.tagline}>
                        Maison d'édition béninoise engagée pour la promotion
                        du livre et des auteurs depuis 2010.
                    </p>

                    {membre && (
                        <div style={S.memberBadge}>
                            <Icon name="shield" size={14} />
                            {membre}
                        </div>
                    )}

                    {/* Adresse rapide */}
                    {contact.addresses?.[0] && (
                        <div style={{ ...S.contactItem, marginTop: '4px' }}>
                            <span style={S.contactIcon}><Icon name="pin" size={14} /></span>
                            <span style={{ fontSize: '0.8rem', color: 'rgba(255,255,255,0.45)' }}>
                                {contact.addresses[0]}
                            </span>
                        </div>
                    )}
                </div>

                {/* Col 2 — Navigation */}
                <div data-aos="fade-up" data-aos-delay="100">
                    <ColTitle>Navigation</ColTitle>
                    <ul style={S.navList} className="protic-footer__links">
                        {[
                            ['Accueil',     '/'],
                            ['Catalogue',   'catalogue'],
                            ['Nos Services','#'],
                            ['À propos',    '/about'],
                            ['Contact',     '/contact'],
                        ].map(([label, href]) => (
                            <FooterNavLink key={label} href={href} label={label} />
                        ))}
                    </ul>
                </div>

                {/* Col 3 — Contact */}
                <div data-aos="fade-up" data-aos-delay="200">
                    <ColTitle>Contact</ColTitle>

                    {contact.telephones?.map((tel, i) => (
                        <div key={i} style={S.contactItem} className="protic-footer__contact-list">
                            <span style={S.contactIcon}><Icon name="phone" size={15} /></span>
                            <a href={`tel:${String(tel).replace(/\s/g,'')}`}
                               style={S.contactLink}
                               onMouseEnter={e => e.target.style.color = '#fff'}
                               onMouseLeave={e => e.target.style.color = 'rgba(255,255,255,0.60)'}>
                                {String(tel)}
                            </a>
                        </div>
                    ))}

                    {contact.emails?.map((mail, i) => (
                        <div key={i} style={S.contactItem} className="protic-footer__contact-list">
                            <span style={S.contactIcon}><Icon name="mail" size={15} /></span>
                            <a href={`mailto:${mail}`}
                               style={S.contactLink}
                               onMouseEnter={e => e.target.style.color = '#fff'}
                               onMouseLeave={e => e.target.style.color = 'rgba(255,255,255,0.60)'}>
                                {mail}
                            </a>
                        </div>
                    ))}
                </div>

                {/* Col 4 — Légal */}
                <div data-aos="fade-up" data-aos-delay="300">
                    <ColTitle>Informations légales</ColTitle>
                    <ul style={S.legalList}>
                        {[
                            ['RCCM',     legal.rccm],
                            ['IFU',      legal.ifu],
                            ['CNSS',     legal.cnss],
                            ['Cpte UBA', legal.compte],
                        ].map(([label, val]) => (
                            <li key={label} style={S.legalItem}>
                                <span style={S.legalLabel}>{label}</span>
                                <span style={S.legalValue}>N° {val}</span>
                            </li>
                        ))}
                    </ul>
                </div>

            </div>

            {/* ── Séparateur dégradé ── */}
            <div style={S.divider} />

            {/* ── Barre copyright ── */}
            <div className="container protic-footer__bottom" style={S.bottom}>
                <span>
                    © {yr} <strong style={S.bottomStrong}>ProTIC Editions &amp; Services</strong>
                    <span style={S.bottomDot} />
                    Tous droits réservés
                </span>
                <span>
                    Plateforme développée par {' '}
                    <a href="https://github.com/Agbokoudjo/" 
                    target="_blank" 
                    rel="noopener"
                    className="text-decoration-none"
                    title="INTERNATIONALES WEB APPS & SERVICES — Agence web Bénin">
                        <strong style={S.bottomStrong}>INTERNATIONALES WEB APPS &amp; SERVICES</strong>
                            {' '}
                        &mdash; AGBOKOUDJO Franck &mdash; +229 01 67 25 18 86
                    </a>
                </span>
            </div>

        </footer>
    );
}
