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
    fetchErrorTranslator
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
            responseType:"json"
        });

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


const handleSuccess = (e) => {
    /**
     * 
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


const handleNetworkError = (e) => {
    /**
     * 
     * @type {FormSubmitRequestErrorEvent} 
     */
    const event = e.detail;
    console.error('[Network Error]', event.requestError.message);
     event.stopPropagation();

    const error =  event.requestError;
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

const handleSubmitServerError = (e) => {
    /**
     * 
     * @type {FormSubmitFailedEvent}
     */
    const event = e.detail;
    const { statusCode, data } = event.response;

    if (statusCode !== 422) {
        showErrorDialog({
        title: 'Erreur',
        message: data.errors,
    })
    
    }
};