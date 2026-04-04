// assets/react/controllers/ContactFormModal.jsx
import React from "react";
import Modal from "./Modal";

export default function ContactFormModal({
    modalSubTitle,
    subject,
    urlSubmit,
    labelBtnClick,
    classNameBtnClick,
}) {
    const [isOpen, setIsOpen] = React.useState(false);

    return (
        <React.Fragment>
            {/* Bouton déclencheur */}
            <button
                className={classNameBtnClick ?? "protic-btn protic-btn--contact protic-btn--full"}
                onClick={() => setIsOpen(true)}
            >
                {labelBtnClick ?? "✉️ Contacter l'auteur"}
            </button>

            <Modal
                idModal="contactModal"
                isOpen={isOpen}
                onClose={() => setIsOpen(false)}
                classParentModal="modal-lg"

                modalHeader={
                    <div>
                        <div style={{ display: "flex", alignItems: "center", gap: "10px", marginBottom: "4px" }}>
                            <div style={{
                                width: "36px", height: "36px",
                                background: "#2D3099", borderRadius: "8px",
                                display: "flex", alignItems: "center",
                                justifyContent: "center", fontSize: "18px", flexShrink: 0
                            }}>
                                📩
                            </div>
                            <h3 className="modal-title" style={{ margin: 0 }}>Contacter l'auteur</h3>
                        </div>
                        <p className="modal-subtitle">{modalSubTitle}</p>
                    </div>
                }

                modalBody={
                    <form
                        id="contactForm"
                        action={urlSubmit}
                        style={{ background: "#F8F7F4", borderRadius: "10px", padding: "20px" }}
                    >
                        <div className="form-group">
                            <label>Nom complet *</label>
                            <input
                                type="text"
                                id="fullName"
                                name="username"
                                placeholder="Ex : AGBOKOUDJO Hounha Franck"
                                data-position-lastname="right"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                pattern="^[\p{L}\p{N}\p{M}\s\-\.]{6,255}$"
                                data-escapestrip-html-and-php-tags="true"
                                maxLength="255"
                                minLength="6"
                                required
                                data-error-message-input="Ce champ doit contenir uniquement des lettres alphabétiques"
                                className="form-control"
                            />
                            <span className="form-error" id="errName"></span>
                        </div>

                        <div className="form-row">
                            <div className="form-group">
                                <label>Email *</label>
                                <input
                                    type="email"
                                    data-type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Ex : franck@gmail.com"
                                    data-event-validate-blur="blur"
                                    data-event-validate-input="input"
                                    required
                                    data-escapestrip-html-and-php-tags="false"
                                    maxLength="200"
                                    minLength="6"
                                    data-error-message-input="Email invalide"
                                    className="form-control"
                                />
                                <span className="form-error" id="errEmail"></span>
                            </div>

                            <div className="form-group">
                                <label>Téléphone *</label>
                                <input
                                    type="tel"
                                    data-type="tel"
                                    id="phone"
                                    name="phone"
                                    placeholder="+229 XX XX XX XX"
                                    data-event-validate-blur="blur"
                                    data-event-validate-input="input"
                                    required
                                    data-escapestrip-html-and-php-tags="true"
                                    maxLength="80"
                                    minLength="8"
                                    data-error-message-input="Numéro de téléphone invalide"
                                    className="form-control"
                                />
                                <span className="form-error" id="errPhone"></span>
                            </div>
                        </div>

                        <div className="form-group">
                            <label>Objet (auto-détecté)</label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                readOnly
                                value={subject ?? ""}
                                className="form-control"
                                style={{ background: "#EDECEA", cursor: "not-allowed" }}
                            />
                        </div>

                        <div className="form-group">
                            <label>Message *</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                data-pattern="^[\p{L}\p{M}\p{N}\s.,`\p{P}\n\r]+$"
                                data-escapestrip-html-and-php-tags="true"
                                maxLength="4000"
                                minLength="20"
                                required
                                placeholder="Précisez le nombre d'exemplaires souhaités et toute autre information utile..."
                                className="form-control"
                            />
                            <span className="form-error" id="errMessage"></span>
                        </div>
                    </form>
                }

                modalFooter={
                    <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end" }}>
                        <button
                            type="button"
                            className="btn btn-secondary"
                            onClick={() => setIsOpen(false)}
                        >
                            Fermer
                        </button>
                        <button
                            type="submit"
                            className="btn btn-primary btn-full"
                            form="contactForm"
                        >
                            📨 Envoyer la demande
                        </button>
                    </div>
                }
            />
        </React.Fragment>
    );
}