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
    
    localStorage.setItem('typeBien', 'Maison');

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

    localStorage.setItem('copropriete', 'Non');

    nextBtn.addEventListener('click', () => {
        localStorage.setItem('surface', surfaceSelect.value);
        localStorage.setItem('anneeConstruction', anneeConstructionSelect.value);
        localStorage.setItem('gazInstallation', gazInstallationSelect.value);
        localStorage.setItem('elecInstallation', elecInstallationSelect.value);

        window.location.href = 'recap.html';
    });
});