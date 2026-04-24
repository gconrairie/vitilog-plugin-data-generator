function bindFixtureToggles() {
    const fields = document.querySelectorAll('[data-control="dg-fixture"]');
    fields.forEach((field) => {
        switch (field.type) {
            case 'checkbox':
            field.addEventListener("change", toggleField);
                break;
            case 'number':
            case 'date':
            case 'text':
                field.addEventListener("input", updateStatus);
                break;
            default:
                field.addEventListener("change", updateStatus);
            break;
        }
    });
}

function toggleField(field) {
    const target = field.target;
    const targetName = target.dataset.field ;
    
    const productionField = document.querySelector('[data-field="production"]');
    const prelevementField = document.querySelector('[data-field="prelevement"]');
    const convocationField = document.querySelector('[data-field="convocation"]');
    const acceptField = document.querySelector('[data-field="accept"]');

    switch (targetName) {
        case 'production':
            if (! target.checked) {
                prelevementField.checked = false;
                convocationField.checked = false;
                acceptField.checked = false;
            }
        break;

        case 'prelevement':
            if (target.checked) 
                productionField.checked = true;
        break;

        case 'convocation':
            if (target.checked) {
                productionField.checked = true;
            } else {
                acceptField.checked = false;
            }
        break;

        case 'accept':
            if (target.checked) {
                productionField.checked = true;
                convocationField.checked = true;
            }
        break;
    }
}

function updateStatus(e) {
    const target = e.target;
    const targetName = target.dataset.field; 

    // Users
    const exploitantSelect = document.querySelector('[data-field="exploitant"]');
    const exploitantEmail = document.querySelector('[data-field="exploitant-email"]');
    const prestataireSelect = document.querySelector('[data-field="prestataire"]');
    const prestataireEmail = document.querySelector('[data-field="prestataire-email"]');

    // Convocation
    const convocationField = document.querySelector('[data-field="convocation"]');

    if( !exploitantSelect || !exploitantEmail || !prestataireSelect || !prestataireEmail) {
        throw new Error('Missing fields');
        return;
    }
    
    switch (targetName) {

        case 'exploitant':
            if (exploitantSelect && exploitantSelect.value > 0) {
                exploitantEmail.value = '';
            }
        break;

        case 'exploitant-email':
            if (exploitantSelect) exploitantSelect.value = 0;
        break;

        case 'prestataire':
            if (prestataireSelect && prestataireSelect.value > 0) {
                prestataireEmail.value = '';
            } else {
                prestataireEmail.value = '';
            }
        break;

        case 'prestataire-email':
            if (prestataireSelect) prestataireSelect.value = 0;
        break;

        case 'date':
            if (target.value) {
                // If a convocation date is provided, convocation must be enabled (not toggled).
                if (!convocationField.checked) {
                    convocationField.checked = true;
                    toggleField({ target: convocationField });
                }
            }
        break;
    }
/*
    

    
        */
}
    
document.addEventListener("DOMContentLoaded", bindFixtureToggles);
document.addEventListener("turbo:load", bindFixtureToggles);