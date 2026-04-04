// assets/react/controllers/BookSummaryModal.jsx
import React from "react";
import Modal from "./Modal";
import ContactFormModal from "./ContactFormModal";
import AuthorBioModal from "./AuthorBioModal";

const formatDate = (dateStr) => {
    if (!dateStr) return null;
    return new Date(dateStr).toLocaleDateString("fr-FR", {
        month: "long",
        year: "numeric",
    });
};

export default function BookSummaryModal({ book }) {
    const [isOpen, setIsOpen] = React.useState(false);

    return (
        <React.Fragment>
            <button
                className="protic-btn protic-btn--sm protic-btn--dark btn-resume"
                onClick={() => setIsOpen(true)}
            >
                📄 Résumé
            </button>

            <Modal
                idModal="resumeModal"
                isOpen={isOpen}
                onClose={() => setIsOpen(false)}
                classParentModal="modal-resume"

                modalHeader={
                    <div style={{ display: "flex", alignItems: "center", gap: "12px", marginBottom: "20px" }}>
                        <div style={{
                            width: "48px", height: "64px",
                            background: "linear-gradient(135deg, #2D3099, #4a52c4)",
                            borderRadius: "6px",
                            display: "flex", alignItems: "center",
                            justifyContent: "center",
                            fontSize: "24px", flexShrink: 0
                        }}>
                            📖
                        </div>
                        <div>
                            <h3 className="modal-title" style={{ marginBottom: "4px" }}>
                                {book?.title}
                            </h3>
                            <p style={{ fontSize: "13px", color: "#6b7280", margin: 0 }}>
                                ✍️ {book?.author?.fullName} 
                            </p>
                        </div>
                    </div>
                }

                modalBody={
                    <div>
                        <div style={{ display: "flex", gap: "8px", flexWrap: "wrap", marginBottom: "16px" }}>
                            {book?.category?.name && (
                                <span style={{
                                    background: "#f0f4ff", color: "#2D3099",
                                    fontSize: "11px", fontWeight: 600,
                                    padding: "4px 10px", borderRadius: "6px"
                                }}>
                                    {book.category.name}
                                </span>
                            )}
                            {book?.publishedAt && (
                                <span style={{
                                    background: "#fffbeb", color: "#b45309",
                                    fontSize: "11px", fontWeight: 600,
                                    padding: "4px 10px", borderRadius: "6px"
                                }}>
                                    📅 {formatDate(book.publishedAt)}
                                </span>
                            )}
                            {book?.isbn && (
                                <span style={{
                                    background: "#f9fafb", color: "#6b7280",
                                    fontSize: "11px",
                                    padding: "4px 10px", borderRadius: "6px"
                                }}>
                                    ISBN : {book.isbn}
                                </span>
                            )}
                        </div>

                        {/* Résumé */}
                        <div style={{
                            background: "#f8fafc",
                            borderLeft: "4px solid #2D3099",
                            borderRadius: "0 8px 8px 0",
                            padding: "16px 20px",
                            marginBottom: "8px"
                        }}>
                            <p style={{ fontSize: "14px", color: "#374151", lineHeight: 1.8, margin: 0 }}>
                                {book?.summary ?? "Résumé non disponible."}
                            </p>
                        </div>
                    </div>
                }

                modalFooter={
                    <div style={{ display: "flex", gap: "10px", width: "100%" }}>

                        {/* ContactFormModal gère son propre état — on ferme resumeModal avant */}
                        <ContactFormModal
                            modalSubTitle={`📚 ${book?.title} — ${book?.author?.firstName} ${book?.author?.lastName}`}
                            subject={`Commande — ${book?.title ?? ""}`}
                            urlSubmit="/api/contact-author"
                            labelBtnClick="📩 Commander un exemplaire"
                            classNameBtnClick="btn btn-primary"
                            onTrigger={() => setIsOpen(false)}
                            
                        />

                        {/* AuthorBioModal gère son propre état — on ferme resumeModal avant */}
                        <AuthorBioModal
                            author={book?.author}
                            labelBtnClick="👤 Voir la biographie"
                            classNameBtnClick="btn"
                            styleBtnClick={{
                                flex: 1,
                                background: "#f0f4ff",
                                color: "#2D3099",
                                border: "none",
                                cursor: "pointer",
                                fontWeight: 600,
                                borderRadius: "8px"
                            }}
                            onTrigger={() => setIsOpen(false)}
                            
                        />
                    </div>
                }
            />
        </React.Fragment>
    );
}