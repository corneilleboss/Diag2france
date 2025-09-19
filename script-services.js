document.addEventListener('DOMContentLoaded', async () => {
    const servicesContainer = document.querySelector('.container');
    const backBtn = document.querySelector('.back-btn');
    const continueBtn = document.querySelector('.continue-btn');
    const totalDiv = document.createElement('div');
    totalDiv.id = 'total-price';
    totalDiv.style.fontWeight = 'bold';
    totalDiv.style.fontSize = '2rem';
    totalDiv.style.textAlign = 'center';
    totalDiv.style.marginTop = '20px';
    
    servicesContainer.insertBefore(totalDiv, continueBtn);

    const selectedDiagnostiqueur = JSON.parse(localStorage.getItem('selectedDiagnostiqueur'));
    const checkedDiagnosticsCount = parseInt(localStorage.getItem('checkedDiagnosticsCount'));
    const userLat = parseFloat(localStorage.getItem('latitude'));
    const userLon = parseFloat(localStorage.getItem('longitude'));
    
    // Grille tarifaire pour les diagnostics obligatoires
    const tarifs = {
        'moins-20': { 'prix': [110, 140, 170, 200, 230, 265, 290] },
        'moins-40': { 'prix': [130, 165, 200, 235, 270, 305, 340] },
        'moins-60': { 'prix': [155, 195, 235, 275, 315, 355, 395] },
        'moins-80': { 'prix': [185, 230, 275, 320, 365, 410, 455] },
        'moins-100': { 'prix': [215, 270, 325, 380, 435, 490, 545] },
        'moins-120': { 'prix': [250, 315, 380, 445, 510, 575, 640] },
        'moins-140': { 'prix': [280, 355, 430, 505, 580, 655, 730] },
        'moins-160': { 'prix': [310, 390, 470, 550, 630, 710, 790] },
        'moins-180': { 'prix': [335, 420, 505, 590, 675, 760, 845] },
        'moins-200': { 'prix': [365, 455, 545, 635, 725, 815, 905] },
        'moins-220': { 'prix': [390, 485, 580, 675, 770, 865, 960] },
    };

    if (!selectedDiagnostiqueur || isNaN(checkedDiagnosticsCount) || isNaN(userLat) || isNaN(userLon)) {
        servicesContainer.innerHTML = `<p style="color: red;">Erreur: Informations manquantes. Veuillez recommencer le processus.</p>`;
        continueBtn.disabled = true;
        return;
    }
    
    let prixBase = 0;
    const surface = localStorage.getItem('surface');
    if (tarifs[surface] && tarifs[surface].prix[checkedDiagnosticsCount - 2]) {
        prixBase = tarifs[surface].prix[checkedDiagnosticsCount - 2];
    } else {
        servicesContainer.innerHTML = `<p style="color: red;">Erreur: Grille tarifaire non trouvée pour cette surface.</p>`;
    }
    
    let selectedServices = [];

    function calculerPrixTotal() {
        let prixSupplementaires = 0;
        selectedServices = [];
        document.querySelectorAll('.service-checkbox:checked').forEach(checkbox => {
            prixSupplementaires += parseFloat(checkbox.dataset.price);
            selectedServices.push({
                name: checkbox.dataset.name,
                price: parseFloat(checkbox.dataset.price)
            });
        });

        const prixTotal = prixBase + prixSupplementaires;
        totalDiv.textContent = `Prix total : ${prixTotal} €`;
        localStorage.setItem('totalPrice', prixTotal);
    }

    async function fetchServices() {
        const url = 'https://hubhabitats.ekkleo.com/api/v1/get_services';
        const body = {
            latitude: userLat,
            longitude: userLon,
            partner_id: selectedDiagnostiqueur.partner_id,
            limit: 100000000
        };

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const result = await response.json();

            if (result.error || !result.data || result.data.length === 0) {
                servicesContainer.innerHTML += `<p>Aucun service additionnel n'a été trouvé pour ce diagnostiqueur.</p>`;
                return;
            }

            const servicesSectionTitle = document.createElement('h2');
            servicesSectionTitle.textContent = "Services Additionnels";
            servicesSectionTitle.style.textAlign = 'center';
            servicesContainer.insertBefore(servicesSectionTitle, totalDiv);

            result.data.forEach(service => {
                const card = document.createElement('div');
                card.className = 'service-card';
                card.innerHTML = `
                    <div class="service-info">
                        <img src="${service.image_of_the_service}" alt="Image du service">
                        <div class="service-details">
                            <p class="service-name">${service.title}</p>
                            <p class="service-description">${service.description}</p>
                        </div>
                    </div>
                    <div class="service-price">
                        <p>${service.price_with_tax}€</p>
                        <input type="checkbox" class="service-checkbox" data-price="${service.price_with_tax}" data-name="${service.title}">
                    </div>
                `;
                servicesContainer.insertBefore(card, totalDiv);
                
                card.querySelector('.service-checkbox').addEventListener('change', calculerPrixTotal);
            });

            calculerPrixTotal();

        } catch (error) {
            console.error('Erreur lors de la récupération des services:', error);
            servicesContainer.innerHTML += `<p style="color: red;">Une erreur est survenue lors du chargement des services. Veuillez réessayer plus tard.</p>`;
        }
    }

    fetchServices();

    continueBtn.addEventListener('click', () => {
        // Sauvegarde les services sélectionnés et le prix total pour la page de rendez-vous
        localStorage.setItem('selectedServices', JSON.stringify(selectedServices));
        window.location.href = 'appointment.html';
    });

    backBtn.addEventListener('click', () => {
        window.history.back();
    });
});