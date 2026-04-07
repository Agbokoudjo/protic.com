import {
    FormValidateController,
    addHashToIds,
    FieldValidationFailed,
} from '@wlindabla/form_validator';


window.addEventListener('DOMContentLoaded',()=>{
    const form_exist = document.querySelector('form.form-validate');
    if (form_exist ===null) {
        return;
    }
    
    const form_validate = new FormValidateController('.form-validate');
    const __form = form_validate.form;

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

    __form.on(FieldValidationFailed, (event) => {
        const data = (event.originalEvent).detail;

        form_validate.addErrorMessageChildrenForm(
            jQuery(data.targetChildrenForm),
            data.message,
            'container-div-error-message');
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

