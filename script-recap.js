document.addEventListener('DOMContentLoaded', () => {
    const nextStepBtn = document.querySelector('.next-step-btn');
    const backBtn = document.querySelector('.back-btn');
    const recapContainer = document.querySelector('.container');
    const diagnosticsTitle = document.querySelector('h3');

    const anneeConstruction = localStorage.getItem('anneeConstruction');
    const gazInstallation = localStorage.getItem('gazInstallation');
    const elecInstallation = localStorage.getItem('elecInstallation');
    const copropriete = localStorage.getItem('copropriete');

    function afficherDiagnostics() {
        let diagnostics = [];

        if (anneeConstruction === 'avant-1949') {
            diagnostics = ['DPE', 'AMIANTE', 'PLOMB', 'CARREZE BOUTIN', 'ERP'];
        } else if (anneeConstruction === 'apres-1949') {
            diagnostics = ['DPE', 'AMIANTE', 'ERP', 'CARREZE BOUTIN'];
        } else if (anneeConstruction === 'apres-1997') {
            diagnostics = ['DPE', 'ERP', 'CARREZE BOUTIN'];
        }

        if (gazInstallation === 'plus-15ans') {
            diagnostics.push('GAZ');
        }
        if (elecInstallation === 'plus-15ans') {
            diagnostics.push('ELECTRICITE');
        }

        // Ajout de la recherche termite
        const selectedAddress = localStorage.getItem('selectedAddress');
        // On suppose que la ville est le dernier mot de l'adresse
        const villeExtraite = selectedAddress ? selectedAddress.trim().split(' ').pop().toLowerCase() : '';

        fetch('termite_base.json')
            .then(response => response.json())
            .then(data => {
                const villeTrouvee = data.some(item => item.ville && item.ville.toLowerCase() === villeExtraite);
                if (villeTrouvee && !diagnostics.includes('TERMITES')) {
                    diagnostics.push('TERMITES');
                }

                localStorage.setItem('checkedDiagnosticsCount', diagnostics.length);

                if (diagnostics.length > 0) {
                    diagnostics.forEach(diag => {
                        const card = document.createElement('div');
                        card.className = 'card-recap';
                        card.innerHTML = `
                            <input type="checkbox" id="check-${diag.toLowerCase()}" class="recap-checkbox" checked>
                            <label for="check-${diag.toLowerCase()}">
                                <p class="card-title">Diagnostic ${diag}</p>
                                <div class="card-content">Obligatoire</div>
                            </label>
                        `;
                        recapContainer.insertBefore(card, nextStepBtn);
                    });
                    setupCheckboxValidation();
                }
            });
    }

    function setupCheckboxValidation() {
        const checkboxes = document.querySelectorAll('.recap-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (event) => {
                const checkedCount = document.querySelectorAll('.recap-checkbox:checked').length;
                localStorage.setItem('checkedDiagnosticsCount', checkedCount);
                if (checkedCount < 2) {
                    event.target.checked = true;
                    alert('Vous devez sélectionner au moins 2 diagnostics.');
                }
            });
        });
    }

    afficherDiagnostics();

    nextStepBtn.addEventListener('click', () => {
        window.location.href = 'diagnostiqueurs.html';
    });
    
    backBtn.addEventListener('click', () => {
        window.history.back();
    });
});