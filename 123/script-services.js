document.addEventListener('DOMContentLoaded', () => {
    const backBtn = document.querySelector('.back-btn');
    const payBtn = document.querySelector('.pay-btn');
    const totalDiv = document.createElement('div');
    totalDiv.id = 'total-price';
    totalDiv.style.fontWeight = 'bold';
    totalDiv.style.fontSize = '2rem';
    totalDiv.style.textAlign = 'center';
    totalDiv.style.marginTop = '20px';
    
    payBtn.parentNode.insertBefore(totalDiv, payBtn);

    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    const surface = localStorage.getItem('surface');
    
    // Grille tarifaire unifiée incluant les prix réduits si applicables
    // Le dernier prix est le prix réduit
    const tarifs = {
        'moins-20': { 'prix': [110, 140, 170, 200, 230, 265, 290] },
        'moins-40': { 'prix': [130, 165, 200, 235, 270, 305, 340] },
        'moins-60': { 'prix': [155, 195, 235, 275, 315, 355, 395], 'promo': 550 },
        'moins-80': { 'prix': [185, 230, 275, 320, 365, 410, 455], 'promo': 250 },
        'moins-100': { 'prix': [220, 270, 320, 370, 420, 470, 520], 'promo': 470 },
        'moins-120': { 'prix': [260, 315, 370, 425, 480, 535, 590], 'promo': 535 },
        'moins-140': { 'prix': [295, 330, 395, 450, 505, 575, 625], 'promo': 355 },
        'moins-160': { 'prix': [335, 355, 420, 485, 550, 615, 680] },
        'moins-180': { 'prix': [360, 405, 475, 545, 615, 685, 755], 'promo': 2160 },
        'moins-200': { 'prix': [405, 455, 530, 605, 680, 755, 830] },
        'moins-220': { 'prix': [455, 510, 585, 660, 735, 810, 885] },
    };

    function calculerPrixTotal() {
        const checkedDiagnosticsCount = parseInt(localStorage.getItem('checkedDiagnosticsCount')) || 0;
        
        const surfaceKey = surface.replace('m2', '').trim();
        const tarifEntry = tarifs[surfaceKey];

        if (!tarifEntry || checkedDiagnosticsCount < 2) {
            totalDiv.textContent = `Prix total : 0 €`;
            return;
        }

        let prixNormal = 0;
        let prixReduit = 0;

        const prixList = tarifEntry.prix;
        
        if (checkedDiagnosticsCount - 2 < prixList.length) {
            prixNormal = prixList[checkedDiagnosticsCount - 2];
        }

        // Vérifie si un prix réduit est défini pour cette surface et ce nombre de diagnostics
        // La logique est complexe car la grille de prix réduite n'est pas complète
        // J'ai pris l'hypothèse que la réduction s'applique sur le dernier élément
        if (checkedDiagnosticsCount === prixList.length + 1 && tarifEntry.promo) {
             prixReduit = tarifEntry.promo;
        } else {
             prixReduit = prixNormal;
        }

        let servicesSupplementaires = 0;
        serviceCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                servicesSupplementaires += parseInt(checkbox.dataset.price);
            }
        });

        const prixTotal = prixReduit + servicesSupplementaires;
        
        if (prixNormal !== prixReduit) {
            totalDiv.innerHTML = `<span class="old-price">Prix total: ${prixNormal} €</span><br>Prix total réduit: ${prixTotal} €`;
        } else {
            totalDiv.textContent = `Prix total : ${prixTotal} €`;
        }
    }

    backBtn.addEventListener('click', () => {
        window.history.back();
    });

    payBtn.addEventListener('click', () => {
        alert(`Paiement de ${totalDiv.textContent} en cours...`);
    });

    serviceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', calculerPrixTotal);
    });

    calculerPrixTotal();
});