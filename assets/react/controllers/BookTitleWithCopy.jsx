
import React from "react";
import { useCopyLink } from "./hooks";

export default function BookTitleWithCopy({ book }){
    const { copied, copyLink } = useCopyLink(book.slug);

    return (
        <div className="cat-book-card__title-wrap">
            <h3 className="cat-book-card__title">{book.title}</h3>
            {book.slug && (
                <button
                    className={`cat-book-card__copy-btn${copied ? ' is-copied' : ''}`}
                    onClick={copyLink}
                    title={copied ? 'Lien copié !' : 'Copier le lien'}
                    aria-label={copied ? 'Lien copié !' : `Copier le lien de ${book.title}`}
                    type="button"
                >
                    {copied
                        ? <i className="bi bi-check-circle-fill" aria-hidden="true"></i>
                        : <i className="bi bi-link-45deg" aria-hidden="true"></i>
                    }
                    <span className="cat-book-card__copy-label">
                        {copied ? 'Lien copié !' : 'Copier le lien'}
                    </span>
                </button>
            )}
        </div>
    );
};
