document.addEventListener("DOMContentLoaded", function () {
    initBatch();
})

export function initBatch() {
    const batchMaster = document.getElementById('list_batch_checkbox');
    if (!batchMaster) return;

    /**
     * Met à jour l'apparence de la ligne sans réveiller les vieux scripts Sonata
     * @param {HTMLInputElement} checkbox 
     */
    const updateRowStyle = (checkbox) => {
        const row = checkbox.closest('tr');
        if (!row) return;

        if (checkbox.checked) {
            // On ajoute la classe pour le système, mais on force le style propre
            row.classList.add('sonata-ba-list-row-selected');
            row.style.backgroundColor = "rgba(60, 141, 188, 0.1)"; // Bleu léger moderne
            row.style.boxShadow = "none"; // On tue l'ombre jaune de force ici aussi
        } else {
            row.classList.remove('sonata-ba-list-row-selected');
            row.style.backgroundColor = "";
            row.style.boxShadow = "";
        }
    };

    // 1. Gestion du "Tout cocher / Tout décocher" (Master Checkbox)
    batchMaster.addEventListener('click', function () {
        const isChecked = this.checked;
        const table = this.closest('table');
        if (!table) return;

        // On cible uniquement les checkboxes de la colonne batch
        const checkboxes = table.querySelectorAll('.sonata-ba-list-field-batch input[type="checkbox"]');

        checkboxes.forEach(checkbox => {
            if (checkbox !== batchMaster) {
                checkbox.checked = isChecked;
                updateRowStyle(checkbox);
            }
        });
    });

    // 2. Gestion des clics individuels sur chaque ligne
    const listCheckboxes = document.querySelectorAll('.sonata-ba-list-field-batch input[type="checkbox"]:not(#list_batch_checkbox)');
    
    listCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('click', function () {
            updateRowStyle(this);
            
            // Si on décoche une ligne, on décoche aussi le Master
            if (!this.checked) {
                batchMaster.checked = false;
            }
        });

        // Initialisation au chargement (pour les lignes déjà cochées par le serveur)
        updateRowStyle(checkbox);
    });
}