import React, { useState, useEffect, useRef } from 'react';
import Swal from 'sweetalert2';
import {
    FormSubmission,
    FormSubmitRequestEvents,
    FormSubmitSuccessEvent,
    FormSubmitRequestErrorEvent,
    FormSubmitFailedEvent,
    showSuccessDialog,
    showErrorDialog,
    fetchErrorTranslator,
    handleErrorsManyForm,
    ApiError
} from '@wlindabla/form_validator';

import { HttpFetchError } from "@wlindabla/http_client";
/**
 * 
 * @param { React.RefObject<HTMLFormElement>} formRef 
 * @param {string} url 
 */
export function useFormSubmission(formRef, url, isOpen) {
    const [isLoading, setIsLoading] = useState(false);
    const submissionRef = useRef(null);

    useEffect(() => {
      if (!isOpen || !formRef.current) return;

        const handleStart = () => setIsLoading(true);
        const handleEnd = () => setIsLoading(false);

        window.addEventListener(FormSubmitRequestEvents.FORM_SUBMIT_START, handleStart);
        window.addEventListener(FormSubmitRequestEvents.FORM_SUBMIT_END, handleEnd);
        window.addEventListener(FormSubmitRequestEvents.FORM_SUBMIT_SUCCESS, handleSuccess);
        window.addEventListener(FormSubmitRequestEvents.FORM_SUBMIT_ERROR, handleNetworkError);
        window.addEventListener(FormSubmitRequestEvents.FORM_SUBMIT_FAILED, handleSubmitServerError);

        return () => {
            window.removeEventListener(FormSubmitRequestEvents.FORM_SUBMIT_START, handleStart);
            window.removeEventListener(FormSubmitRequestEvents.FORM_SUBMIT_END, handleEnd);
            window.removeEventListener(FormSubmitRequestEvents.FORM_SUBMIT_SUCCESS, handleSuccess);
            window.removeEventListener(FormSubmitRequestEvents.FORM_SUBMIT_ERROR, handleNetworkError);
            window.removeEventListener(FormSubmitRequestEvents.FORM_SUBMIT_FAILED, handleSubmitServerError);
            submissionRef.current?.processStop();
        };
    }, [formRef, url,isOpen]);

    /**
     * 
     * @param {React.SubmitEvent} e 
     */
    const submit = async (e) => {
        e.preventDefault();
        
        if (!formRef.current) return;

        const submission = new FormSubmission(formRef.current, {
            url,
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            mustRedirect: false,
            responseType: "json",
            credentials: 'include'
        });

        submission.withHandleErrorsManyForm(false);

        submission.confirmMethodRequest = async (message) => {
            const result = await Swal.fire({
                title: 'Confirmer l\'envoi',
                text: message || "Voulez-vous vraiment envoyer votre demande ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2D3099', 
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, envoyer !',
                cancelButtonText: 'Annuler'
            });

            return result.isConfirmed;
        };

        submissionRef.current = submission;
        setIsLoading(true);

        try {
            await submission.processStart();
        } catch (err) {
            console.error("Erreur :", err);
        } finally {
            setIsLoading(false);
        }
    };

    return { submit, isLoading };
}

/**
 * @param {CustomEvent} e
 */
const handleSuccess = (e) => {
    /***
     * @type {FormSubmitSuccessEvent} 
    */
    const event = e.detail;
    const res = event.resultHttpResponse.fetchResponse;
    const form = event.formElement;

    showSuccessDialog({
        title: 'Succès !',
        message: res.data.message 
    })
    form.reset();
};

/**
 * 
 * @param {CustomEvent}  e
 * @returns 
 */
const handleNetworkError = (e) => {
     /**
      * @type {FormSubmitRequestErrorEvent}  
      */
    const event = e.detail;
    const error = event.requestError
    console.error('[Network Error]', error);
     event.stopPropagation();

    if (!(error instanceof HttpFetchError) || !(error.cause instanceof Error)) {
        return;
    }
        
    const errorName = error.cause.name;
    const messageError = fetchErrorTranslator.trans(errorName, error);
        
    showErrorDialog({
        title:  'Erreur Réseau',
        message: messageError || 'Impossible de joindre le serveur. Vérifiez votre connexion',
    })
};

/**
 * @param {CustomEvent} e 
 */
const handleSubmitServerError = (e) => {
    const event = e.detail;
    const error_res = event.response;

    // Si ce n'est pas une erreur de validation (422)
    if (error_res.statusCode !== 422) {
        // Symfony peut renvoyer .detail (RFC 7807) ou .message
        const msg = error_res.data?.errors || error_res.data?.detail || error_res.data?.message || "Erreur serveur";
        showErrorDialog({
            title: 'Erreur',
            message: msg,
        });
        return;
    }

    // Récupération des données (On vérifie si c'est déjà un objet ou s'il faut parser)
    let errorData;
    if (typeof error_res.data === 'string') {
        try {
            errorData = JSON.parse(error_res.data);
        } catch (e) {
            console.error("Impossible de parser le JSON d'erreur", error_res.data);
            return;
        }
    } else {
        errorData = error_res.data; // C'est déjà l'objet { "country": "..." }
    }
    // Application des erreurs au formulaire
    try {
        const form = event.formElement;

        handleErrorsManyForm(form.name, form.id,errorData);
    } catch (error) {
        console.error("Erreur lors du traitement handleErrorsManyForm:", error,errorData);
    }
};