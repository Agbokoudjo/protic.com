import '@vitejs/plugin-react/preamble';
import React from "react";
import { createPortal } from "react-dom";

export default function Modal({ 
    classParentModal = "",
    idModal,
    modalHeader,
    modalBody,
    modalFooter,
    isOpen,
    onClose
}) {
    // Fermeture avec la touche Escape + blocage du scroll
     React.useEffect(() => {
        if (!isOpen) return;

        const handleKey = (e) => {
            if (e.key === "Escape") onClose();
        };

        document.addEventListener("keydown", handleKey);
        document.body.style.overflow = "hidden";

        return () => {
            document.removeEventListener("keydown", handleKey);
            document.body.style.overflow = "";
        };
    }, [isOpen, onClose]);

    // Ne rien rendre si la modale est fermée
    if (!isOpen) return null;

    return createPortal(
        <div
            className="modal-overlay"
            id={idModal}
            onClick={onClose}       // clic sur l'overlay = fermeture
            role="dialog"
            aria-modal="true"
            aria-labelledby={`${idModal}-title`}
        >
            <div
                className={`modal ${classParentModal}`.trim()}
                tabIndex="-1"
               
                onClick={(e) => e.stopPropagation()} // empêche la fermeture au clic dans la modale
            >
                <div className="modal-dialog">
                    <div className="modal-content">

                        <div className="modal-header" id={`${idModal}-header`}>
                            {modalHeader}
                            <button
                                type="button"
                                className="btn-close modal-close"
                                onClick={onClose}
                                aria-label="Fermer"
                            >
                                ×
                            </button>
                        </div>

                        <div className="modal-body">
                            {modalBody}
                        </div>

                        {modalFooter && (
                            <div className="modal-footer">
                                {modalFooter}
                            </div>
                        )}

                    </div>
                </div>
            </div>
        </div>,
        document.body
    );
}