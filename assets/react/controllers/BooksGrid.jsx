// assets/react/controllers/BooksGrid.jsx
import '@vitejs/plugin-react/preamble';
import React from "react";
import { safeFetch } from '@wlindabla/http_client';
import { addParamToUrl } from "@wlindabla/form_validator";
import BookSummaryModal from "./BookSummaryModal";
import AuthorBioModal from "./AuthorBioModal";
import ContactFormModal from "./ContactFormModal";
import placeholderImage from "../../images/placeholder_before_load.png";

const API_BASE    = "/api/books";
const PLACEHOLDER = placeholderImage;

/* ══════════════════════════════════════════════════════════════
   Skeleton loader
══════════════════════════════════════════════════════════════ */
const Skeleton = ({ count = 6 }) => (
    <div className="protic-books-grid">
        {Array.from({ length: count }).map((_, i) => (
            <div key={i} className="protic-book-card protic-book-card--skeleton">
                <div className="protic-book-card__cover-skeleton" />
                <div className="protic-book-card__body">
                    <div className="skeleton-line" />
                    <div className="skeleton-line skeleton-line--short" />
                    <div className="skeleton-line skeleton-line--btn" />
                </div>
            </div>
        ))}
    </div>
);

/* ══════════════════════════════════════════════════════════════
   Pagination
══════════════════════════════════════════════════════════════ */
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
        <div className="protic-pagination">
            <button
                className="protic-pagination__btn"
                disabled={currentPage === 1}
                onClick={() => onChange(currentPage - 1)}
                aria-label="Page précédente"
            >‹</button>

            {getPages().map((p, i) =>
                p === "..." ? (
                    <span key={`ellipsis-${i}`} className="protic-pagination__ellipsis">…</span>
                ) : (
                    <button
                        key={p}
                        className={`protic-pagination__btn ${p === currentPage ? "protic-pagination__btn--active" : ""}`}
                        onClick={() => onChange(p)}
                        aria-current={p === currentPage ? "page" : undefined}
                    >{p}</button>
                )
            )}

            <button
                className="protic-pagination__btn"
                disabled={currentPage === totalPages}
                onClick={() => onChange(currentPage + 1)}
                aria-label="Page suivante"
            >›</button>
        </div>
    );
};

/* ══════════════════════════════════════════════════════════════
   BookCard
   Ordre visuel des boutons dans .protic-book-card__body :
     1. ✉️  Contacter l'auteur   → order: -1 via CSS (remonte au-dessus)
     2. 📄  Résumé               → flex:1, côté gauche
     3. 👤  Biographie           → flex:1, côté droit
══════════════════════════════════════════════════════════════ */
const BookCard = ({ book }) => {
    const [imgError, setImgError] = React.useState(false);

    const cover = imgError || !book.coverImage
        ? PLACEHOLDER
        : `uploads/cover_image_book/${book.coverImage}`;

    return (
        <div className="protic-book-card">

            {/* Badge catégorie */}
            {book.category?.name && (
                <span className="protic-book-card__badge">
                    {book.category.name}
                </span>
            )}

            {/* Couverture */}
            <div className="protic-book-card__cover">
                <img
                    src={cover}
                    alt={`Couverture — ${book.title}`}
                    loading="lazy"
                    onError={() => setImgError(true)}
                />
            </div>

            {/* Corps */}
            <div className="protic-book-card__body">
                <h3 className="protic-book-card__title">{book.title}</h3>
                <p className="protic-book-card__author">{book.author?.fullName}</p>

                {/*
                  Le bouton Contacter est rendu ICI dans le flux,
                  mais remonté visuellement via `order: -1` en CSS
                  sur la classe .protic-btn--contact
                */}
                <ContactFormModal
                    modalSubTitle={`📚 ${book.title} — ${book.author?.fullName}`}
                    subject={`Commande — ${book.title ?? ""}`}
                    urlSubmit="/api/contact-author"
                    labelBtnClick="✉️ Contacter l'auteur"
                    classNameBtnClick="protic-btn protic-btn--contact protic-btn--full"
                     bookId={book.id}
                />

                {/* Résumé (gauche) + Biographie (droite) */}
                <div className="protic-book-card__actions">
                    <BookSummaryModal book={book} />

                    <AuthorBioModal
                        author={book.author}
                        labelBtnClick="👤 Biographie"
                        classNameBtnClick="protic-btn protic-btn--sm protic-btn--ghost-dark"
                        bookId={book.id}
                        bookTitle={book.title}
                    />
                </div>
            </div>
        </div>
    );
};

/* ══════════════════════════════════════════════════════════════
   BooksGrid — composant principal
══════════════════════════════════════════════════════════════ */
export default function BooksGrid({ limit = 12, mode = "catalogue" }) {
    const isHome       = mode === "home";
    const itemsPerPage = isHome ? limit : 12;

    const [books,      setBooks]      = React.useState([]);
    const [loading,    setLoading]    = React.useState(true);
    const [error,      setError]      = React.useState(false);
    const [page,       setPage]       = React.useState(1);
    const [totalPages, setTotalPages] = React.useState(1);

    const fetchBooks = React.useCallback(async (p = 1) => {
        setLoading(true);
        setError(false);
        try {
            const res = await fetchRequestBooks(p, itemsPerPage);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const data    = res.data;
            // Compatibilité hydra:member (ld+json) et member (json simple)
            const members = data["hydra:member"]     ?? data["member"]     ?? [];
            const total   = data["hydra:totalItems"] ?? data["totalItems"] ?? members.length;

            setBooks(members);
            setTotalPages(Math.ceil(total / itemsPerPage));
        } catch (err) {
            console.error("[BooksGrid]", err);
            setError(true);
        } finally {
            setLoading(false);
        }
    }, [itemsPerPage]);

    React.useEffect(() => { fetchBooks(page); }, [page, fetchBooks]);

    React.useEffect(() => {
        if (!loading && books.length > 0 && window.AOS) window.AOS.refresh();
    }, [loading, books]);

    if (loading) return <Skeleton count={itemsPerPage} />;

    if (error) return (
        <div className="protic-error">
            <i className="bi bi-wifi-off" style={{ fontSize: "2rem", display: "block", marginBottom: "12px" }} />
            <p>Impossible de charger les livres. Vérifiez votre connexion.</p>
            <button className="protic-btn protic-btn--ghost" onClick={() => fetchBooks(page)}>
                🔄 Réessayer
            </button>
        </div>
    );

    if (!books.length) return (
        <p className="protic-empty">Aucun livre disponible pour le moment.</p>
    );

    return (
        <React.Fragment>
            <div className="protic-books-grid">
                {books.map((book) => <BookCard key={book.id} book={book} />)}
            </div>

            {!isHome && (
                <Pagination
                    currentPage={page}
                    totalPages={totalPages}
                    onChange={(p) => {
                        setPage(p);
                        window.scrollTo({ top: 0, behavior: "smooth" });
                    }}
                />
            )}
        </React.Fragment>
    );
}

/* ══════════════════════════════════════════════════════════════
   Fetch helper
══════════════════════════════════════════════════════════════ */
async function fetchRequestBooks(page, itemsPerPage) {
    return await safeFetch({
        url: addParamToUrl(API_BASE, {
            "order[publishedAt]": "desc",
            itemsPerPage,
            page,
        }, true),
        retryCount: 3,
        timeout: 55000,
        responseType: "json",
        retryOnStatusCode: true,
        methodSend: "GET",
        keepalive: true,
        headers: {
            'Accept': 'application/ld+json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    });
}
