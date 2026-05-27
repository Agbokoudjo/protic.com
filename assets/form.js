import {formatterEvent} from '@wlindabla/form_validator/formatting';

export function formformatterEventHandle() {
    formatterEvent.lastnameToUpperCase(document);
    formatterEvent.capitalizeUsername(document);
    formatterEvent.usernameFormatDom(document);
 }

import Swal from 'sweetalert2';

import {
    FormValidateController,
    addHashToIds,
    fetchErrorTranslator,
    HttpRequestSubscriber,
    FormSubmitRequestEvents,
    FormSubmitFailedEvent,
    FormSubmitSuccessEvent,
    PrepareRequestFormSubmitEvent,
    showLoadingDialog,
    showSuccessDialog,
    showErrorDialog,
    FormSubmission,
    eventDispatcherBrowser,
    handleErrorsManyForm
} from '@wlindabla/form_validator';

window.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.app-frontend')) {
    return;
    }

   const form_exist = document.querySelector('form.form-validate');
    if (form_exist ===null) {
        return;
    }
    
    const form_validate = new FormValidateController('.form-validate');
    const __form = jQuery(form_validate.form);

    const idsBlur = addHashToIds(form_validate.idChildrenUsingEventBlur).join(",");
    const idsInput = addHashToIds(form_validate.idChildrenUsingEventInput).join(",");
    const idsChange = addHashToIds(form_validate.idChildrenUsingEventChange).join(",");
    const idsDragenter=addHashToIds(form_validate.idChildrenUsingEventDragenter).join(",");

    __form.on("blur", `${idsBlur}`, async (event) => {
        const target = event.target;
        if ((target instanceof HTMLInputElement ||
            target instanceof HTMLTextAreaElement)
           && target.type !== "file") {

            await form_validate.validateChildrenForm(target);
        }
    });

    __form.on('field:validation:failed', (event) => {
        const data = (event.originalEvent).detail;

        form_validate.addErrorMessageChildrenForm(
            data.targetChildrenForm,
            data.message,
            'container-div-error-message');
    });

    __form.on('field:validation:success', (event) => {
        const data = (event.originalEvent).detail;
        form_validate.clearErrorDataChildren(data.targetChildrenForm);
     });
    
    __form.on('input', `${idsInput}`, (event) => {
        const target = event.target;
        if ((target instanceof HTMLInputElement ||
            target instanceof HTMLTextAreaElement)
             && target.type !== "file") {

            form_validate.clearErrorDataChildren(target);
           
        }
    });
    __form.on('change', `${idsChange}`, async (event) => {
        const target = event.target;
        form_validate.clearErrorDataChildren(target);
        if (target instanceof HTMLInputElement && target.type === "file") {
            await form_validate.validateChildrenForm(target);
        }
    })
    __form.on('dragenter',`${idsDragenter}`, (event) => {
        const target = event.target;
        if (target instanceof HTMLInputElement && target.type === "file") {

           form_validate.clearErrorDataChildren(target);
        }
    });
})

export class FormSubmissionSubscriber extends HttpRequestSubscriber
{
    constructor(
    ) {
        super(fetchErrorTranslator) ;
    }

    /**
     * @return Record<string, string | { listener: string; priority?: number | undefined; }> 
     */
    getSubscribedEvents(){
        return {
            [FormSubmitRequestEvents.FORM_SUBMIT_PREPARE_REQUEST]: { listener: "onPrepareRequest", priority: 100 },
            [FormSubmitRequestEvents.FORM_SUBMIT_SUCCESS]: { listener: "onFormSubmitSuccess", priority: 100 },
            [FormSubmitRequestEvents.FORM_SUBMIT_FAILED]: { listener: "onFormSubmitFailed", priority: 100 },
            ...super.getSubscribedEvents()
        } ;
    }

    /**
     * 
     * @param  {PrepareRequestFormSubmitEvent} event
     * @returns void
     */
    async onPrepareRequest(event) {
        event.stopPropagation();
        /**
         * @type {HTMLFormElement}
         */
        const form = event.formElement;
        if (!form.classList.contains('form-submission-handle-auto')) { return; }

        Swal.close();
        showLoadingDialog({config:{
            title: await window.SonataTranslator.trans('FORM_SUBMISSION_PROGRESS_TITLE','sonata-translations'),
            text: await window.SonataTranslator.trans('FORM_SUBMISSION_PROGRESS_MESSAGE','sonata-translations')
        }})
    }

    /**
     * 
     * @param {FormSubmitSuccessEvent} event
     * @returns void
     */
    async onFormSubmitSuccess(event) {
        event.stopPropagation();

        /**
         * @type {HTMLFormElement}
         */
        const form = event.formElement;
        if (!form.classList.contains('form-submission-handle-auto')) { return; }

        const { fetchResponse } = event.resultHttpResponse;
        const { title, message } = fetchResponse.data;
        Swal.close();
        form.reset();
        await showSuccessDialog({
            title: title || 'Success',
            message: message
        })
        
    }

    /**
     * 
     * @param {FormSubmitFailedEvent} event
     * @returns void
     */
   async onFormSubmitFailed(event) {
        event.stopPropagation();

        /**
         * @type {HTMLFormElement}
         */
        const form = event.formElement;
        if (!form.classList.contains('form-submission-handle-auto')) { return; }

       const fetchResponse = event.response;
        const { title, errorMessage,violations,details,message } = fetchResponse.data;
        
       if (fetchResponse.statusCode === 422 || 
          fetchResponse.statusCode === 400) {
           if (violations) {
               handleErrorsManyForm(
                form.name ?? form.id,
                form.id,
              violations
            )
           }
       }
        
        Swal.close() ;
        await showErrorDialog({
            title: title || 'Error',
            message: errorMessage || details || message || fetchResponse.data //lorsque le serveur renvoie une exception que nous controllons pas
        })
    }
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) ||
        !form.classList.contains('form-submission-handle-auto')) {
        return;
    }

    if (!document.querySelector('.app-frontend')) {
    return;
    }

    event.preventDefault();

   const formSubmission = new FormSubmission(form, {
        url: form.action || window.location.href,
        headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
        responseType: "json",
        timeout: 60000,
        retryOnStatusCode: false
    },
     false,
        eventDispatcherBrowser
    );

    formSubmission.withHandleErrorsManyForm(false);

    formSubmission.confirmMethodRequest = async (message) => {
            const result = await Swal.fire({
                title: 'Confirmer l\'envoi',
                text: message || "Voulez-vous vraiment envoyer votre demande ?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2D3099', 
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, envoyer !',
                cancelButtonText: 'Annuler',
                background: "#00427E",
                color: "#fff"
            });

            return result.isConfirmed;
        };

    try {
        await formSubmission.processStart();
    } catch (err) {
        console.error("Erreur :", err);
    } 
})

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.app-frontend')) {
    return;
    }
    formformatterEventHandle();
   eventDispatcherBrowser.addSubscriber(new FormSubmissionSubscriber());
});