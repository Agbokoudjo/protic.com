import React from 'react';
import { createRoot } from 'react-dom/client';
import ContactFormModal from './ContactFormModal';

export class ContactFormModalElement extends HTMLElement {
    connectedCallback() {
        if (this._root) return;

        const bookId    = this.dataset.bookId;
        const bookTitle = this.dataset.bookTitle;
        const authorName = this.dataset.authorName;
        const subject   = this.dataset.subject;
        const labelBtnClick=this.dataset.labelBtnClick || "✉️ Commander ce livre" ;
        const classNameBtnClick=this.dataset.classNameBtnClick || "bk-btn bk-btn--primary bk-btn--full" ;

        if (!bookId) {
            console.warn('[ContactFormModalElement] data-book-id manquant');
            return;
        }
        
        this.style.display = 'block';

        this._root = createRoot(this);

        this._root.render(
            <ContactFormModal
                bookId={parseInt(bookId, 10)}
                modalSubTitle={`📚 ${bookTitle} — ${authorName}`}
                subject={subject}
                labelBtnClick={labelBtnClick}
                classNameBtnClick={classNameBtnClick}
            />
        );
    }

    disconnectedCallback() {
        // Nettoyer React proprement quand l'élément est retiré du DOM
        this._root?.unmount();
         this._root = null;
    }
}