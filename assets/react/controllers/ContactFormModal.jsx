// assets/react/controllers/ContactFormModal.jsx
import React, { useRef, useState }  from 'react';
import Modal from "./Modal";
import {useFormSubmission} from "./hooks";

import { countries } from "countries-list";
import { addParamToUrl } from '@wlindabla/form_validator';
import { showErrorDialog } from '@wlindabla/form_validator/utils';
import { formInputValidator } from '@wlindabla/form_validator/validation/core/router';

const ALL_COUNTRIES = Object.entries(countries)
    .map(([code, data]) => {
        return { 
            alpha3: data.languages[0] || code,
            id: code,
            name: data.name, 
            emoji: data.emoji 
        };
    })
    .sort((a, b) => a.name.localeCompare(b.name, document.documentElement.lang ?? "fr"));
    
export default function ContactFormModal({
    modalSubTitle,
    subject,
    labelBtnClick,
    classNameBtnClick,
    bookId
}) {
    if (!bookId) {
        throw new Error("Attention : bookId est manquant dans ContactFormModal !");
    }

    const [isOpen, setIsOpen] = useState(false);
    const [errors, setErrors] = useState({});
    const formRef = useRef(null);
    const fullNameRef = useRef(null);
    const emailRef = useRef(null);
    const phoneRef = useRef(null);
    const messageRef = useRef(null);

    const { submit, isLoading } = useFormSubmission(formRef, addParamToUrl("/api/contact-author"),isOpen);

    const validateField = async (fieldName, value, type, options) => {
        await formInputValidator.allTypesValidator(value, fieldName, type, options);
        const v = formInputValidator.getValidator(fieldName);
        const fieldErrors = v?.formErrorStore.getFieldErrors(fieldName) ?? [];
        setErrors(prev => ({ ...prev, [fieldName]: fieldErrors[0] }));
    };
    // Gestion de la soumission du formulaire
    const handleFormSubmit = async (e) => {
        e.preventDefault();

        // Récupération des valeurs courantes
        const fullName = fullNameRef.current?.value ?? '';
        const email = emailRef.current?.value ?? '';
        const phone = phoneRef.current?.value ?? '';
        const message = messageRef.current?.value ?? '';

        await Promise.all([
            validateField('fullName', fullName,  'text',     validateFieldOptions("text")),
            validateField('email',    email,     'email',    validateFieldOptions("email")),
            validateField('phone',    phone,     'tel',      validateFieldOptions("tel")),
            validateField('message',  message,   'textarea', validateFieldOptions("textarea"))
        ]);

        // Vérification si tous les champs sont valides
        const fieldsToValidate = ['fullName', 'email', 'phone', 'message'];
        const allValid = fieldsToValidate.every(f => {
            const v = formInputValidator.getValidator(f);
            return v?.formErrorStore.isFieldValid(f) ?? true;
        });

        // Si tout est valide, on laisse ton hook `useFormSubmission` envoyer les données
        if (!allValid) {
            await showErrorDialog({
            title: "Erreur de validation",
            message: "Certains champs sont invalides. Veuillez vérifier vos saisies."
        });
            return;
        }

        await submit(e);
    };
    return (
        <React.Fragment>
            {/* Bouton déclencheur */}
            <button
                className={classNameBtnClick ?? "protic-btn protic-btn--contact protic-btn--full"}
                onClick={() => setIsOpen(true)}
                disabled={isLoading}
            >
                {isLoading ? "Envoi en cours..." : `${ labelBtnClick ??  "✉️ Contacter l'auteur"}`}
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
                        action="api/contact-author"
                        name="contact"
                        ref={formRef} 
                        onSubmit={handleFormSubmit}
                        data-turbo="false"
                        style={{ background: "#F8F7F4", borderRadius: "10px", padding: "20px" }}
                        className="form-validate"
                    >
                        <input 
                            type="hidden" 
                            name="bookId" 
                            value={bookId} 
                            readOnly 
                            style={{ 
                                position: 'absolute', 
                                opacity: 0, 
                                pointerEvents: 'none', 
                                zIndex: -1 
                            }} 
                        />
                        <div className="form-group">
                            <label>Nom complet *</label>
                            <input
                                type="text"
                                onBlur={async (e) =>
                                    await validateField('fullName', e.target.value, 'text', validateFieldOptions("text"))
                                }
                                onChange={() => setErrors(prev => ({ ...prev, fullName: undefined }))}
                                id="contact_fullName" 
                                name="fullName"
                                 ref={fullNameRef}
                                placeholder="Ex : AGBOKOUDJO Hounha Franck"
                                data-position-lastname="right"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                pattern="^[\p{L}\p{N}\p{M}\s]{6,255}$"
                                data-escapestrip-html-and-php-tags="true"
                                maxlength="255"
                                minlength="6"
                                required
                                data-error-message-input="Ce champ doit contenir uniquement des lettres alphabétiques"
                                className="form-control"
                            />
                            {errors.fullName && <span className="form-error text-danger" id="errName">{errors.fullName}</span>}
                        </div>

                       <div className="form-group">
                            <label>Email *</label>
                            <input
                                type="email"
                                data-type="email"
                                id="contact_email"
                                 ref={emailRef}
                                name="email"
                                placeholder="Ex : franck@gmail.com"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                required
                                data-escapestrip-html-and-php-tags="false"
                                maxlength="200"
                                minlength="6"
                                data-error-message-input="Email invalide"
                                className="form-control"
                                onBlur={async (e) =>
                                    await validateField('email', e.target.value, 'email', validateFieldOptions("email"))
                                }
                                onChange={() => setErrors(prev => ({ ...prev, email: undefined }))}
                            />
                            {errors.email && <span className="form-error text-danger" id="errEmail">{errors.email}</span>}
                        </div>
                        <div className="form-group">
                            <label>Téléphone *</label>
                            <input
                                type="tel"
                                data-type="tel"
                                id="contact_phone"
                                ref={phoneRef}
                                name="phone"
                                placeholder="+229 XX XX XX XX"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                required
                                data-escapestrip-html-and-php-tags="true"
                                maxlength="80"
                                minlength="8"
                                data-error-message-input="Numéro de téléphone invalide"
                                className="form-control"
                                 onBlur={async (e) =>
                                    await validateField('phone', e.target.value, 'tel', validateFieldOptions("tel"))
                                }
                                onChange={() => setErrors(prev => ({ ...prev, phone: undefined }))}
                            />
                           {errors.phone && <span className="form-error text-danger" id="errPhone">{errors.phone}</span>}
                        </div>
                        <div className="form-group">
                            <label htmlFor="cf-country">Pays</label>
                            <select
                                id="contact_country"
                                name="country"
                                defaultValue=""
                                className="form-control select2 form-select-lg"
                                aria-label="Sélectionnez votre pays"
                            >
                                <option value="" disabled>🌍 Sélectionnez votre pays…</option>
                                {ALL_COUNTRIES.map(c => (
                                    <option key={c.id} value={c.alpha3}>
                                        {c.emoji} {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="form-group">
                            <label>Objet (auto-détecté)</label>
                            <input
                                type="text"
                                id="contact_subject"
                                name="subject"
                                readOnly
                                value={subject ?? ""}
                                className="form-control"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                data-pattern="^[\p{L}\p{N}\p{M}\s\-\.\p{P}\,\(\)]+$"
                                data-escapestrip-html-and-php-tags="true"
                                maxlength="255"
                                minlength="6"
                                style={{ background: "#EDECEA", cursor: "not-allowed" }}
                            />
                        </div>

                        <div className="form-group">
                            <label>Message *</label>
                            <textarea
                                id="contact_message"
                                name="message"
                                rows="5"
                                data-event-validate-blur="blur"
                                data-event-validate-input="input"
                                 data-type ='textarea'
                                data-pattern= '^[^\u003C\u003E\u0060\u0000-\u001F\u007F]+$'
                                data-match='false'
                                data-flag-pattern ='us'
                                data-escapestrip-html-and-php-tags="true"
                                maxlength="4000"
                                minlength="20"
                                ref={messageRef}
                                required
                                placeholder="Précisez le nombre d'exemplaires souhaités et toute autre information utile..."
                                className="form-control"
                                 onBlur={async (e) =>
                                    await validateField('message', e.target.value, 'textarea', validateFieldOptions("textarea"))
                                }
                                onChange={() => setErrors(prev => ({ ...prev,message: undefined }))}
                            />
                            {errors.message && <span className="form-error text-danger" id="errMessage">{errors.message}</span>}
                        </div>
                    </form>
                }

                modalFooter={
                    <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end" }}>
                        <button
                            type="button"
                            className="btn btn-secondary"
                            onClick={() => setIsOpen(false)}
                            disabled={isLoading}
                        >
                            Fermer
                        </button>
                        <button
                            type="submit"
                            className="btn btn-primary btn-full"
                            form="contactForm"
                            data-iwas-confirm="Voulez-vous vraiment envoyer votre demande ?"
                            data-submits-with="Envoi en cours…"
                            disabled={isLoading}
                        > {isLoading ? "Patientez..." : "📨 Envoyer la demande"}
                        </button>
                    </div>
                }
            />
        </React.Fragment>
    );
}



const validateFieldOptions=(type)=>{
    switch(type){
        case "text":
            return { 
                requiredInput: true,
                minLength: 6, maxLength: 255,
                regexValidator: /[\p{L}\p{M}\p{N}\s]/ui,
                match: true,
                escapestripHtmlAndPhpTags: true,
                errorMessageInput: "Votre nom et prénoms sont invalides",
                egAwait: "AGBOKOUDJO Franck",
                typeInput: "text"
             }
        case "textarea":
             return { 
                requiredInput: true,
                minLength: 20, maxLength: 4000,
                regexValidator: new RegExp(
            '(<[^>]*>|<\\/[^>]+>|&[#a-zA-Z0-9]+;|javascript\\s*:|data\\s*:|vbscript\\s*:|on\\w+\\s*=|<\\?php|\\?>|\\{\\{|\\}\\}|\\$\\{)',
            'ius'
        ),
                match: false,
                escapestripHtmlAndPhpTags: true,
                errorMessageInput: "Le message de votre commande est invalide",
                typeInput: 'textarea',
             }
        case "email":
             return { 
                requiredInput: true, 
                minLength: 6, maxLength: 200,
                match:true,
                escapestripHtmlAndPhpTags:false,
                errorMessageInput: "votre addresse email est invalide",
                typeInput: 'email',
                hostBlacklist:_hostBlacklist,
             }
        case "tel":
             return { 
                requiredInput: true, 
                minLength: 8, maxLength: 80,
                match:true,
                escapestripHtmlAndPhpTags:true,
                errorMessageInput: "Numéro de téléphone invalide",
                 typeInput: 'tel',
                 defaultCountry: 'BJ',
                egAwait: "+229 XX XX XX XX",
             }
        break ;
        default:
            return {}
    }
}

const _hostBlacklist= [
    // Classiques jetables
    'tempmail.com', 'tempmail.org', 'tempmail.net',
    'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org',
    'guerrillamail.biz', 'guerrillamail.de', 'guerrillamail.info',
    'mailinator.com', 'mailinator.net', 'mailinator.org',
    'yopmail.com', 'yopmail.fr', 'yopmail.net',
    'trashmail.com', 'trashmail.at', 'trashmail.me',
    'trashmail.net', 'trashmail.org', 'trashmail.io',
    'sharklasers.com', 'guerrillamailblock.com',
    'grr.la', 'spam4.me', 'dispostable.com',

    // 10minutemail & variantes
    '10minutemail.com', '10minutemail.net', '10minutemail.org',
    '10minutemail.co.uk', '10minutemail.de', '10minemail.com',

    // Throwaway
    'throwam.com', 'throwaway.email', 'throwam.com',
    'fakeinbox.com', 'fake-box.com', 'maildrop.cc',
    'spamgourmet.com', 'spamgourmet.net', 'spamgourmet.org',

    // Autres populaires
    'mailnull.com', 'spamcowboy.com', 'spamcowboy.net',
    'spamcowboy.org', 'spamevader.com', 'getairmail.com',
    'discard.email', 'spamfree24.org', 'spamfree24.de',
    'spamfree24.eu', 'spamfree24.info', 'spamfree24.net',
    'mailnew.com', 'spamex.com', 'binkmail.com',
    'bobmail.info', 'chammy.info', 'devnullmail.com',
    'fudgerub.com', 'jobbikmail.com', 'yuurok.com',
    'discardmail.com', 'discardmail.de', 'spamspot.com',
    'spamthisplease.com', 'tempinbox.com', 'tempr.email',
    'discard.email', 'sharklasers.com', 'spam.la',
    'inoutmail.de', 'inoutmail.eu', 'inoutmail.info',
    'inoutmail.net', 'filzmail.com', 'weg-werf-email.de',
    'wegwerfmail.de', 'wegwerfmail.net', 'wegwerfmail.org',
    'meltmail.com', 'anonymbox.com', 'courriel.fr.nf',
    'cool.fr.nf', 'jetable.fr.nf', 'nospam.ze.tc',
    'nomail.xl.cx', 'mega.zik.dj', 'speed.1s.fr',
    'courriel.fr.nf', 'iwi.net', 'jetable.net',
    'jetable.org', 'nospam.ze.tc',
]

