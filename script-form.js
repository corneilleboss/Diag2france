document.addEventListener('DOMContentLoaded', () => {
    const adresseAffichee = document.getElementById('adresse-affichee');
    const maisonBtn = document.getElementById('maison-btn');
    const appartementBtn = document.getElementById('appartement-btn');
    const coproprieteOuiBtn = document.getElementById('copropriete-oui');
    const coproprieteNonBtn = document.getElementById('copropriete-non');
    const surfaceSelect = document.getElementById('surface-select');
    const anneeConstructionSelect = document.getElementById('annee-construction');
    const gazInstallationSelect = document.getElementById('gaz-installation');
    const elecInstallationSelect = document.getElementById('elec-installation');
    const nextBtn = document.querySelector('.next-btn');

    const selectedAddress = localStorage.getItem('selectedAddress');

    if (selectedAddress) {
        adresseAffichee.textContent = selectedAddress;
    }

    maisonBtn.addEventListener('click', () => {
        maisonBtn.classList.add('active');
        appartementBtn.classList.remove('active');
        localStorage.setItem('typeBien', 'Maison');
    });

    appartementBtn.addEventListener('click', () => {
        appartementBtn.classList.add('active');
        maisonBtn.classList.remove('active');
        localStorage.setItem('typeBien', 'Appartement');
    });
    
    if (!localStorage.getItem('typeBien')) {
        localStorage.setItem('typeBien', 'Maison');
    }

    coproprieteOuiBtn.addEventListener('click', () => {
        coproprieteOuiBtn.classList.add('active');
        coproprieteNonBtn.classList.remove('active');
        localStorage.setItem('copropriete', 'Oui');
    });

    coproprieteNonBtn.addEventListener('click', () => {
        coproprieteNonBtn.classList.add('active');
        coproprieteOuiBtn.classList.remove('active');
        localStorage.setItem('copropriete', 'Non');
    });

    if (!localStorage.getItem('copropriete')) {
        localStorage.setItem('copropriete', 'Non');
    }

    const modal = document.getElementById('client-info-modal');
    const clientForm = document.getElementById('client-info-form');

    nextBtn.addEventListener('click', (event) => {
        event.preventDefault(); 
        
        localStorage.setItem('surface', surfaceSelect.value);
        localStorage.setItem('anneeConstruction', anneeConstructionSelect.value);
        localStorage.setItem('gazInstallation', gazInstallationSelect.value);
        localStorage.setItem('elecInstallation', elecInstallationSelect.value);

        modal.style.display = 'flex';
    });

    clientForm.addEventListener('submit', (event) => {
        event.preventDefault();

        const clientType = document.querySelector('input[name="client-type"]:checked').value;
        let valid = true;

        // Stocke le type de client dans le localStorage
        localStorage.setItem('client-type', clientType);

        if (clientType === 'particulier') {
            const name = document.getElementById('client-name').value;
            const surname = document.getElementById('client-surname').value;
            const email = document.getElementById('client-email').value;
            const phone = document.getElementById('client-phone').value;
            const lot = document.getElementById('client-lot').value;
            if (!name || !surname || !email || !phone || !lot) valid = false;
            localStorage.setItem('client-name', name);
            localStorage.setItem('client-surname', surname);
            localStorage.setItem('client-email', email);
            localStorage.setItem('client-phone', phone);
            localStorage.setItem('client-lot', lot);
        } else {
            const repName = document.getElementById('pro-rep-name').value;
            const phone = document.getElementById('pro-phone').value;
            const address = document.getElementById('pro-address').value;
            const form = document.getElementById('pro-form').value;
            const quality = document.getElementById('pro-quality').value;
            const email = document.getElementById('pro-email').value;
            const siret = document.getElementById('pro-siret').value;
            const tva = document.getElementById('pro-tva').value;
            const lot = document.getElementById('pro-numlot').value;
            if (!repName || !phone || !address || !form || !quality || !email || !siret || !tva) valid = false;
            localStorage.setItem('pro-rep-name', repName);
            localStorage.setItem('pro-phone', phone);
            localStorage.setItem('pro-address', address);
            localStorage.setItem('pro-form', form);
            localStorage.setItem('pro-quality', quality);
            localStorage.setItem('pro-email', email);
            localStorage.setItem('pro-siret', siret);
            localStorage.setItem('pro-tva', tva);
            localStorage.setItem('pro-numlot', lot);
        }

        if (!valid) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return;
        }

        modal.style.display = 'none';
        window.location.href = 'recap.html';
    });

});

// Gestion de l'affichage des champs client selon le type
document.addEventListener('DOMContentLoaded', () => {
    const clientTypeRadios = document.querySelectorAll('input[name="client-type"]');
    const particulierFields = document.getElementById('particulier-fields');
    const professionnelFields = document.getElementById('professionnel-fields');

    function setRequiredFields(type) {
        // Pour particulier
        particulierFields.querySelectorAll('input, select').forEach(el => {
            el.required = (type === 'particulier');
        });
        // Pour professionnel
        professionnelFields.querySelectorAll('input, select').forEach(el => {
            el.required = (type === 'professionnel');
        });
    }

    clientTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'particulier') {
                particulierFields.style.display = 'block';
                professionnelFields.style.display = 'none';
                setRequiredFields('particulier');
            } else {
                particulierFields.style.display = 'none';
                professionnelFields.style.display = 'block';
                setRequiredFields('professionnel');
            }
        });
    });

    // Initialisation au chargement
    const checked = document.querySelector('input[name="client-type"]:checked');
    setRequiredFields(checked ? checked.value : 'particulier');
});