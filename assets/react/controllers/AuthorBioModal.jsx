// assets/react/controllers/AuthorBioModal.jsx
import React from "react";
import Modal from "./Modal";
import ContactFormModal from "./ContactFormModal";

export default function AuthorBioModal({
    author,
    labelBtnClick,
    classNameBtnClick,
    styleBtnClick,
    onTrigger,       // callback optionnel : ferme la modale parente avant d'ouvrir celle-ci
}) {
    const [isOpen, setIsOpen] = React.useState(false);

    const handleOpen = () => {
        if (onTrigger) onTrigger(); // ferme la modale parente (BookSummaryModal) si besoin
        setIsOpen(true);
    };

    return (
        <React.Fragment>
            <button
                className={classNameBtnClick ?? "btn-author-bio protic-btn protic-btn--sm protic-btn--ghost-dark"}
                style={styleBtnClick ?? {}}
                onClick={handleOpen}
            >
                {labelBtnClick ?? "👤 Biographie"}
            </button>

            <Modal
                idModal="bioModal"
                isOpen={isOpen}
                onClose={() => setIsOpen(false)}
                classParentModal="modal-bio"

                modalHeader={
                    <div style={{ display: "flex", alignItems: "center", gap: "16px", marginBottom: "24px" }}>
                        <div style={{
                            width: "72px", height: "72px",
                            borderRadius: "50%",
                            background: author?.photo
                                ? "transparent"
                                : "linear-gradient(135deg, #2D3099, #4a52c4)",
                            display: "flex", alignItems: "center",
                            justifyContent: "center",
                            fontSize: "32px", flexShrink: 0,
                            overflow: "hidden"
                        }}>
                            {author?.avatarName 
                                ? <img
                                    src={`uploads/avatars/${author.avatarName}`}
                                    alt={`${author.fullName}`}
                                    style={{ width: "100%", height: "100%", objectFit: "cover" }}
                                    className="rounded-circle img-fluid"
                                  />
                                : "👤"
                            }
                        </div>

                        <div>
                            <div style={{
                                fontSize: "11px",
                                textTransform: "uppercase",
                                letterSpacing: "1px",
                                color: "#9ca3af",
                                marginBottom: "4px"
                            }}>
                                Auteur
                            </div>
                            <h3 className="modal-title" style={{ margin: 0 }}>
                                {author?.fullName} 
                            </h3>
                            <div style={{ display: "flex", gap: "12px", marginTop: "8px", flexWrap: "wrap" }}>
                                {author?.phone && (
                                    <span style={{ fontSize: "12px", color: "#6b7280" }}>
                                        📞 {author.phone}
                                    </span>
                                )}
                                {author?.email && (
                                    <span style={{ fontSize: "12px", color: "#6b7280" }}>
                                        ✉️ {author.email}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                }

                modalBody={
                    <div style={{
                        background: "#f8fafc",
                        borderRadius: "10px",
                        padding: "18px",
                        border: "1px solid #e5e7eb"
                    }}>
                        <div style={{
                            fontSize: "11px",
                            fontWeight: 700,
                            textTransform: "uppercase",
                            letterSpacing: "1px",
                            color: "#2D3099",
                            marginBottom: "10px"
                        }}>
                            Biographie
                        </div>
                        <p style={{ fontSize: "14px", color: "#374151", lineHeight: 1.8, margin: 0 }}>
                            {author?.bio ?? "Biographie non disponible."}
                        </p>
                    </div>
                }

                modalFooter={
                    /* ContactFormModal gère son propre état */
                    <ContactFormModal
                        modalSubTitle={`Auteur : ${author?.fullName} `}
                        subject={`Contact — ${author?.fullName}`}
                        urlSubmit="/api/contact-author"
                        labelBtnClick="📩 Contacter cet auteur"
                        classNameBtnClick="btn btn-primary btn-full"
                        onTrigger={() => setIsOpen(false)}
                        
                    />
                }
            />
        </React.Fragment>
    );
}