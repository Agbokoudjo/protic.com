/**
 * FaqComponent.jsx — v2
 * Accordion avec <details>/<summary> natifs
 * Animation CSS pure — aucun calcul JS de hauteur
 */
import React,{ useState, useEffect, useCallback } from 'react';
import { createRoot } from 'react-dom/client';
import { safeFetch } from '@wlindabla/http_client';
import { addParamToUrl } from '@wlindabla/form_validator';

const API_BASE = '/api/faqs';

async function fetchFaqs(page = 1, perPage = 8, category = '') {
    const params = { page, itemsPerPage: perPage };
    if (category) params.category = category;

    return await safeFetch({
        url          : addParamToUrl(API_BASE, params, true),
        retryCount   : 2,
        timeout      : 15000,
        responseType : 'json',
        methodSend   : 'GET',
        keepalive    : true,
        headers      : {
            'Accept'           : 'application/ld+json',
            'X-Requested-With' : 'XMLHttpRequest',
        },
    });
}

const FaqSkeleton = ({ count = 5 }) => (
    <div className="faq-list">
        {Array.from({ length: count }).map((_, i) => (
            <div key={i} className="faq-item faq-item--skeleton">
                <div className="faq-skeleton__q" />
                <div className="faq-skeleton__a" />
            </div>
        ))}
    </div>
);

/* ══════════════════════════════════════════
   Couleurs par catégorie
══════════════════════════════════════════ */
const CAT_COLORS = {
    'Publication'  : { bg: 'rgba(59,130,246,.15)',  color: '#60a5fa', border: 'rgba(59,130,246,.35)' },
    'Tarifs'       : { bg: 'rgba(16,185,129,.15)',  color: '#34d399', border: 'rgba(16,185,129,.35)' },
    'Distribution' : { bg: 'rgba(245,158,11,.15)',  color: '#fbbf24', border: 'rgba(245,158,11,.35)' },
    'Droits'       : { bg: 'rgba(139,92,246,.15)',  color: '#a78bfa', border: 'rgba(139,92,246,.35)' },
    'Général'      : { bg: 'rgba(107,114,128,.15)', color: '#9ca3af', border: 'rgba(107,114,128,.35)' },
};
const defaultCat = { bg: 'rgba(255,215,0,.12)', color: '#FFD700', border: 'rgba(255,215,0,.30)' };

/* ══════════════════════════════════════════
   FaqItem — utilise <details> / <summary>
   L'animation est gérée entièrement en CSS
   via la pseudo-class [open] sur <details>
══════════════════════════════════════════ */
const FaqItem = ({ faq, index }) => {
    const c = CAT_COLORS[faq.category] ?? defaultCat;

    return (
        <details
            className="faq-item"
            data-aos="fade-up"
            data-aos-duration="500"
            data-aos-delay={index * 60}
        >
            <summary className="faq-item__summary">

                <div className="faq-item__summary-inner">
                    {faq.category && (
                        <span
                            className="faq-item__category"
                            style={{ background: c.bg, color: c.color, borderColor: c.border }}
                        >
                            {faq.category}
                        </span>
                    )}
                    <span className="faq-item__question-text">
                        {faq.question}
                    </span>
                </div>

                {/* Icône chevron — animée en CSS via details[open] */}
                <span className="faq-item__chevron" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18"
                         fill="none" stroke="currentColor" strokeWidth="2.5">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </span>

            </summary>

            {/* Réponse — visible quand <details> est open */}
            <div className="faq-item__answer">
                <p className="faq-item__answer-text">
                    {faq.answer}
                </p>
            </div>

        </details>
    );
};

const FaqPagination = ({ current, total, onChange }) => {
    if (total <= 1) return null;

    return (
        <nav className="faq-pagination" aria-label="Pagination des FAQ" data-turbo="false">
            <button
                className="faq-page-btn faq-page-btn--nav"
                onClick={() => onChange(current - 1)}
                disabled={current === 1}
                aria-label="Page précédente"
            >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                     stroke="currentColor" strokeWidth="2.5">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </button>

            {Array.from({ length: total }, (_, i) => i + 1).map(p => (
                <button
                    key={p}
                    className={`faq-page-btn${p === current ? ' faq-page-btn--active' : ''}`}
                    onClick={() => onChange(p)}
                    aria-current={p === current ? 'page' : undefined}
                >
                    {p}
                </button>
            ))}

            <button
                className="faq-page-btn faq-page-btn--nav"
                onClick={() => onChange(current + 1)}
                disabled={current === total}
                aria-label="Page suivante"
            >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                     stroke="currentColor" strokeWidth="2.5">
                    <path d="M9 18l6-6-6-6" />
                </svg>
            </button>
        </nav>
    );
};

const FaqComponent = ({ perPage = 8 }) => {
    const [faqs,       setFaqs]       = useState([]);
    const [loading,    setLoading]    = useState(true);
    const [error,      setError]      = useState(false);
    const [page,       setPage]       = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [total,      setTotal]      = useState(0);
    const [category,   setCategory]   = useState('');

    const categories = ['', 'Publication', 'Tarifs', 'Distribution', 'Droits', 'Général'];

    const load = useCallback(async (pg = 1, cat = '') => {
        setLoading(true);
        setError(false);
        try { 
            const res  = await fetchFaqs(pg, perPage, cat);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = res.data;

            const members = data['hydra:member']     ?? data['member']     ?? [];
            const tot     = data['hydra:totalItems'] ?? data['totalItems'] ?? members.length;

            setFaqs(members);
            setTotal(tot);
            setTotalPages(Math.max(1, Math.ceil(tot / perPage)));
            setPage(pg);
        } catch {
            setError(true);
        } finally {
            setLoading(false);
            if (window.AOS) window.AOS.refresh();
        }
    }, [perPage]);

    /* Rechargement quand la catégorie change */
    useEffect(() => { load(1, category); }, [category, load]);

    const handlePage = pg => {
        load(pg, category);
        document.getElementById('faq')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <div className="faq-wrap">

            {/* ── Filtres catégories ── */}
            <div className="faq-categories" role="group" aria-label="Filtrer les FAQ par catégorie">
                {categories.map(cat => (
                    <button
                        key={cat || 'all'}
                        className={`faq-cat-btn${category === cat ? ' faq-cat-btn--active' : ''}`}
                        onClick={() => setCategory(cat)}
                        aria-pressed={category === cat}
                    >
                        {cat || 'Toutes les questions'}
                    </button>
                ))}
            </div>

            {/* ── Compteur ── */}
            {!loading && !error && (
                <p className="faq-count">
                    <strong>{total}</strong> question{total > 1 ? 's' : ''} disponible{total > 1 ? 's' : ''}
                    {category && <> dans <strong>{category}</strong></>}
                </p>
            )}

            {/* ── Contenu ── */}
            {loading ? (
                <FaqSkeleton count={perPage} />
            ) : error ? (
                <div className="faq-error">
                    <i className="bi bi-wifi-off" style={{ fontSize: '2rem', display: 'block', marginBottom: '12px' }} />
                    <p>Impossible de charger les FAQ. Veuillez réessayer.</p>
                    <button className="ab-btn-glass" style={{ marginTop: '14px', cursor: 'pointer' }}
                            onClick={() => load(page, category)}>
                        Réessayer
                    </button>
                </div>
            ) : faqs.length === 0 ? (
                <div className="faq-empty">
                    <i className="bi bi-question-circle" style={{ fontSize: '2rem', display: 'block', marginBottom: '12px' }} />
                    <p>Aucune question disponible pour cette catégorie.</p>
                </div>
            ) : (
                <div className="faq-list">
                    {faqs.map((faq, i) => (
                        <FaqItem key={faq.id} faq={faq} index={i} />
                    ))}
                </div>
            )}

            {/* ── Pagination ── */}
            {!loading && !error && faqs.length > 0 && (
                <FaqPagination current={page} total={totalPages} onChange={handlePage} />
            )}

        </div>
    );
};

export const mountFaq = () => {
    const rootEl = document.getElementById('faq-root');
    if (!rootEl || rootEl.dataset.mounted) return;

    const perPage = parseInt(rootEl.dataset.perPage ?? '8', 10);

    const observer = new IntersectionObserver(([entry], obs) => {
        if (!entry.isIntersecting) return;
        obs.disconnect();
        if (rootEl.dataset.mounted === 'true') return;
        rootEl.dataset.mounted = 'true';
        rootEl.innerHTML = '';
        const reactRoot = createRoot(rootEl);
        reactRoot.render(<FaqComponent perPage={perPage} />);
    }, { rootMargin: '300px' });

    observer.observe(rootEl);
};
