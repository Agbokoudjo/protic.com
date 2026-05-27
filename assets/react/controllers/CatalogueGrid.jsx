// assets/react/controllers/CatalogueGrid.jsx
// Composant catalogue avec recherche, filtres genre, tri, pagination
import '@vitejs/plugin-react/preamble';
import React from "react";
import { createRoot } from "react-dom/client";
import { safeFetch } from '@wlindabla/http_client';
import { addParamToUrl } from "@wlindabla/form_validator";
import BookSummaryModal from "./BookSummaryModal";
import AuthorBioModal from "./AuthorBioModal";
import ContactFormModal from "./ContactFormModal";
import BookTitleWithCopy from './BookTitleWithCopy';

import placeholderImage from "../../images/placeholder_before_load.png";

const API_BASE    = "/api/books";
const PLACEHOLDER = placeholderImage;
const DEBOUNCE_MS = 380;

/* ══════════════════════════════════════════════════════════════
   Hook debounce
══════════════════════════════════════════════════════════════ */
function useDebounce(value, delay) {
    const [debounced, setDebounced] = React.useState(value);
    React.useEffect(() => {
        const t = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(t);
    }, [value, delay]);
    return debounced;
}

/* ══════════════════════════════════════════════════════════════
   Construction de l'URL avec tous les filtres
══════════════════════════════════════════════════════════════ */
function buildUrl({ page, itemsPerPage, search, genre, sort }) {
    const params = { itemsPerPage, page };

    // Tri
    switch (sort) {
        case 'date_asc':   params['order[publishedAt]'] = 'asc';  break;
        case 'title_asc':  params['order[title]']       = 'asc';  break;
        case 'title_desc': params['order[title]']       = 'desc'; break;
        default:           params['order[publishedAt]'] = 'desc';
    }

    // Recherche — cherche dans le titre ET le nom de l'auteur
    if (search?.trim()) {
        params['title']                  = search.trim();
        // API Platform SearchFilter partial sur author.lastName
        params['author.fullName']        = search.trim();
    }

    // Filtre genre (slug de la catégorie)
    if (genre?.trim()) {
        params['category.slug'] = genre.trim();
    }

    return addParamToUrl(API_BASE, params, true);
}

const Skeleton = ({ count = 12, view = "grid" }) => {
    if (view === "list") {  
        return (
            <div className="cat-books-list" data-turbo="false">
                {Array.from({ length: count }).map((_, i) => (
                    <div key={i} className="cat-book-skeleton--list">
                        <div style={{ width: 72, height: 100, background: 'rgba(255,255,255,.07)', borderRadius: 8, flexShrink: 0 }} />
                        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 8 }}>
                            <div className="cat-book-skeleton__line" />
                            <div className="cat-book-skeleton__line cat-book-skeleton__line--short" />
                            <div className="cat-book-skeleton__line cat-book-skeleton__line--xshort" />
                        </div>
                    </div>
                ))}
            </div>
        );
    }
    return (
        <div className="cat-books-grid" data-turbo="false">
            {Array.from({ length: count }).map((_, i) => (
                <div key={i} className="cat-book-skeleton--grid">
                    <div className="cat-book-skeleton__cover" />
                    <div className="cat-book-skeleton__lines">
                        <div className="cat-book-skeleton__line" />
                        <div className="cat-book-skeleton__line cat-book-skeleton__line--short" />
                        <div className="cat-book-skeleton__line cat-book-skeleton__line--xshort" />
                    </div>
                </div>
            ))}
        </div>
    );
};

const BookCardGrid = ({ book }) => {
    const [imgError, setImgError] = React.useState(false);
    const cover = imgError || !book.coverImage
        ? PLACEHOLDER
        : `uploads/cover_image_book/${book.coverImage}`;

    const year = book.publishedAt
        ? new Date(book.publishedAt).getFullYear()
        : null;

    return (
        <div className="cat-book-card--grid" data-turbo="false">
            <div className="cat-book-card__cover-wrap">
                <img
                    className="cat-book-card__cover"
                    src={cover}
                    alt={`Couverture — ${book.title}`}  
                    loading="lazy"
                    onError={() => setImgError(true)}
                />
                {book.category?.name && (
                    <span className="cat-book-card__genre-badge">{book.category.name}</span>
                )}
                {year && (
                    <span className="cat-book-card__year-badge">{year}</span>
                )}

                {/* Overlay hover avec actions */}
                <div className="cat-book-card__hover-overlay">
                    <BookSummaryModal
                        book={book}
                        labelBtnClick="📄 Lire le résumé"
                        classNameBtnClick="cat-book-card__action-btn cat-book-card__action-btn--primary"
                         bookId={book.id}
                        bookTitle={book.title}
                    />
                    <ContactFormModal
                        modalSubTitle={`📚 ${book.title} — ${book.author?.fullName}`}
                        subject={`Commande — ${book.title ?? ""}`}
                        urlSubmit="/api/contact-author"
                        labelBtnClick="✉️ Commander"
                        classNameBtnClick="cat-book-card__action-btn cat-book-card__action-btn--secondary"
                        bookId={book.id}
                    />
                </div>
            </div>

            <div className="cat-book-card__body">
                <BookTitleWithCopy book={book} /> 
                <p className="cat-book-card__author">{book.author?.fullName}</p>
                <div className="cat-book-card__footer">
                    <AuthorBioModal
                        author={book.author}
                        labelBtnClick="👤 Auteur"
                        classNameBtnClick="cat-book-card__cta"
                        bookId={book.id}
                        bookTitle={book.title}
                    />
                </div>
            </div>
        </div>
    );
};

const BookCardList = ({ book }) => {
    const [imgError, setImgError] = React.useState(false);
    const cover = imgError || !book.coverImage
        ? PLACEHOLDER
        : `uploads/cover_image_book/${book.coverImage}`;

    const year = book.publishedAt
        ? new Date(book.publishedAt).getFullYear()
        : null;
    return (
        <div className="cat-book-card--list" data-turbo="false">
            <img
                className="cat-book-card--list__cover"
                src={cover}
                alt={`Couverture — ${book.title}`}
                loading="lazy"
                onError={() => setImgError(true)}
            />
            <div className="cat-book-card--list__body">
                <div className="cat-book-card--list__meta">
                    {book.category?.name && (
                        <span className="cat-book-card__genre-badge" style={{ position: 'static', fontSize: '10px' }}>
                            {book.category.name}
                        </span>
                    )}
                    {year && <span className="cat-book-card--list__year">{year}</span>}
                </div>
                <h3 className="cat-book-card__title" style={{ fontSize: '.95rem' }}>{book.title}</h3>
                <p className="cat-book-card__author">{book.author?.fullName}</p>
                {book.summary && (
                    <p className="cat-book-card--list__desc">{book.summary}</p>
                )}
            </div>
            <div className="cat-book-card--list__actions">
                <BookSummaryModal
                    book={book}
                    labelBtnClick="📄 Résumé"
                    classNameBtnClick="cat-book-card__cta"
                     bookId={book.id}
                    bookTitle={book.title}
                />
                <ContactFormModal
                    modalSubTitle={`📚 ${book.title} — ${book.author?.fullName}`}
                    subject={`Commande — ${book.title ?? ""}`}
                    urlSubmit="/api/contact-author"
                    labelBtnClick="✉️ Commander"
                    classNameBtnClick="cat-btn-gold"
                    style={{ padding: '7px 14px', fontSize: '.78rem' }}
                    bookId={book.id}
                />
            </div>
        </div>
    );
};

const Pagination = ({ currentPage, totalPages, onChange }) => {
    if (totalPages <= 1) return null;

    const getPages = () => {
        const pages = [];
        const delta = 2;
        const left  = Math.max(1, currentPage - delta);
        const right = Math.min(totalPages, currentPage + delta);
        if (left > 1) { pages.push(1); if (left > 2) pages.push("..."); }
        for (let p = left; p <= right; p++) pages.push(p);
        if (right < totalPages) {
            if (right < totalPages - 1) pages.push("...");
            pages.push(totalPages);
        }
        return pages;
    };

    return (
        <nav className="cat-pagination" aria-label="Pagination du catalogue" data-turbo="false">
            <button
                className="cat-page-btn cat-page-btn--nav"
                disabled={currentPage === 1}
                onClick={() => onChange(currentPage - 1)}
                aria-label="Page précédente"
            >‹</button>

            {getPages().map((p, i) =>
                p === "..." ? (
                    <span key={`e-${i}`} className="cat-page-ellipsis">…</span>
                ) : (
                    <button
                        key={p}
                        className={`cat-page-btn ${p === currentPage ? "is-active" : ""}`}
                        onClick={() => onChange(p)}
                        aria-current={p === currentPage ? "page" : undefined}
                    >{p}</button>
                )
            )}

            <button
                className="cat-page-btn cat-page-btn--nav"
                disabled={currentPage === totalPages}
                onClick={() => onChange(currentPage + 1)}
                aria-label="Page suivante"
            >›</button>
        </nav>
    );
};

export default function CatalogueGrid({ itemsPerPage = 12 }) {
    const [books,      setBooks]      = React.useState([]);
    const [loading,    setLoading]    = React.useState(true);
    const [error,      setError]      = React.useState(false);
    const [page,       setPage]       = React.useState(1);
    const [totalPages, setTotalPages] = React.useState(1);
    const [totalItems, setTotalItems] = React.useState(0);
    const [view,       setView]       = React.useState("grid");

    // Filtres
    const [search,  setSearch]  = React.useState("");
    const [genre,   setGenre]   = React.useState("");
    const [sort,    setSort]    = React.useState("date_desc");

    const debouncedSearch = useDebounce(search, DEBOUNCE_MS);

    /* ── Écoute des événements du DOM (sidebar Twig → React) ── */
    React.useEffect(() => {
        // Recherche depuis hero ou sidebar
        const onSearch = (e) => {
            setSearch(e.detail?.query ?? "");
            setPage(1);
        };
        // Filtre genre depuis pills ou sidebar
        const onGenre = (e) => {
            setGenre(e.detail?.genre ?? "");
            setPage(1);
        };
        // Tri
        const onSort = (e) => {
            setSort(e.detail?.sort ?? "date_desc");
            setPage(1);
        };
        // Vue grille/liste
        const onView = (e) => {
            setView(e.detail?.view ?? "grid");
        };
        // Reset
        const onReset = () => {
            setSearch(""); setGenre(""); setSort("date_desc"); setPage(1);
        };

        document.addEventListener("protic:catalogue:search", onSearch);
        document.addEventListener("protic:catalogue:genre",  onGenre);
        document.addEventListener("protic:catalogue:sort",   onSort);
        document.addEventListener("protic:catalogue:view",   onView);
        document.addEventListener("protic:catalogue:reset",  onReset);

        return () => {
            document.removeEventListener("protic:catalogue:search", onSearch);
            document.removeEventListener("protic:catalogue:genre",  onGenre);
            document.removeEventListener("protic:catalogue:sort",   onSort);
            document.removeEventListener("protic:catalogue:view",   onView);
            document.removeEventListener("protic:catalogue:reset",  onReset);
        };
    }, []);

    /* ── Fetch ── */
    const fetchBooks = React.useCallback(async (p = 1) => {
        setLoading(true);
        setError(false);
        try {
            const url = buildUrl({ page: p, itemsPerPage, search: debouncedSearch, genre, sort });
            const res = await safeFetch({
                url,
                retryCount: 2,
                timeout: 30000,
                responseType: "json",
                methodSend: "GET",
                credentials: 'include',
                headers: {
                    'Accept': 'application/ld+json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data    = res.data;
            const members = data["hydra:member"]     ?? data["member"]     ?? [];
            const total   = data["hydra:totalItems"] ?? data["totalItems"] ?? members.length;

            setBooks(members);
            setTotalItems(total);
            setTotalPages(Math.ceil(total / itemsPerPage));

            // Met à jour le compteur dans la barre résultats
            const label = document.getElementById("cat-results-label");
            if (label) {
                label.innerHTML = total > 0
                    ? `<strong>${total}</strong> livre${total > 1 ? 's' : ''} trouvé${total > 1 ? 's' : ''}${debouncedSearch ? ` pour « ${debouncedSearch} »` : ''}`
                    : `Aucun résultat${debouncedSearch ? ` pour « ${debouncedSearch} »` : ''}`;
            }

            // Refresh AOS
            if (window.AOS) window.AOS.refresh();

        } catch (err) {
            console.error("[CatalogueGrid]", err);
            setError(true);
        } finally {
            setLoading(false);
        }
    }, [itemsPerPage, debouncedSearch, genre, sort]);

    // Recharge quand les filtres ou la page changent
    React.useEffect(() => {
        fetchBooks(page);
    }, [page, fetchBooks]);

    // Reset page quand les filtres changent
    React.useEffect(() => {
        setPage(1);
    }, [debouncedSearch, genre, sort]);

    /* ── Rendu ── */
    if (loading) return <Skeleton count={itemsPerPage} view={view} />;

    if (error) return (
        <div className="cat-error">
            <i className="bi bi-wifi-off"></i>
            <p>Impossible de charger le catalogue. Vérifiez votre connexion.</p>
            <button className="cat-btn-gold" onClick={() => fetchBooks(page)} style={{ margin: '0 auto' }}>
                🔄 Réessayer
            </button>
        </div>
    );

    if (!books.length) return (
        <div className="cat-empty">
            <i className="bi bi-search"></i>
            <h3>Aucun livre trouvé</h3>
            <p>
                {debouncedSearch
                    ? `Aucun résultat pour « ${debouncedSearch} ». Essayez un autre terme.`
                    : "Aucun livre disponible pour ce filtre."
                }
            </p>
            <button
                className="cat-btn-gold"
                style={{ margin: '0 auto' }}
                onClick={() => document.dispatchEvent(new CustomEvent("protic:catalogue:reset"))}
            >
                Effacer les filtres
            </button>
        </div>
    );

    return (
        <React.Fragment>
            {view === "grid" ? (
                <div className="cat-books-grid">
                    {books.map(book => <BookCardGrid key={book.id} book={book} />)}
                </div>
            ) : (
                <div className="cat-books-list">
                    {books.map(book => <BookCardList key={book.id} book={book} />)}
                </div>
            )}

            <Pagination
                currentPage={page}
                totalPages={totalPages}
                onChange={(p) => {
                    setPage(p);
                    document.getElementById("catalogue")
                        ?.scrollIntoView({ behavior: "smooth", block: "start" });
                }}
            />
        </React.Fragment>
    );
} 

export const mountCatalogue = () => {
    const root = document.getElementById('catalogue-books-root');
    if (!root || root.dataset.mounted) return;
    root.dataset.mounted = 'true';

    const perPage = parseInt(root.dataset.perPage ?? '12', 10);
    createRoot(root).render(<CatalogueGrid itemsPerPage={perPage} />);
};