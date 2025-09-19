document.addEventListener('DOMContentLoaded', async () => {
    const diagnostiqueursContainer = document.getElementById('diagnostiqueurs-container');
    const continueBtn = document.getElementById('continue-btn');
    const backBtn = document.querySelector('.back-btn');

    const userLat = parseFloat(localStorage.getItem('latitude'));
    const userLon = parseFloat(localStorage.getItem('longitude'));
    const checkedDiagnosticsCount = parseInt(localStorage.getItem('checkedDiagnosticsCount')) || 0;
    const surface = localStorage.getItem('surface');

    if (isNaN(userLat) || isNaN(userLon)) {
        diagnostiqueursContainer.innerHTML = `<p style="color: red;">Erreur: Coordonnées de l'adresse non trouvées. Veuillez recommencer le processus.</p>`;
        continueBtn.disabled = true;
        return;
    }
    
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
    
    let prixBase = 0;
    if (tarifs[surface] && tarifs[surface].prix[checkedDiagnosticsCount - 2]) {
        prixBase = tarifs[surface].prix[checkedDiagnosticsCount - 2];
    }
    
    // Le nombre de services supplémentaires est égal au nombre de diagnostics sélectionnés.
    const servicesCount = checkedDiagnosticsCount;

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const toRad = (deg) => deg * (Math.PI / 180);
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return (R * c).toFixed(1);
    }

    function createDiagnostiqueurCard(data) {
        const card = document.createElement('div');
        card.className = 'diagnostiqueur-card';
        card.dataset.id = data.id;
        card.dataset.name = data.company_name;

        const diagnostiqueurLat = parseFloat(data.latitude);
        const diagnostiqueurLon = parseFloat(data.longitude);
        let distanceText = 'Non disponible';

        if (!isNaN(diagnostiqueurLat) && !isNaN(diagnostiqueurLon)) {
            const distance = calculateDistance(userLat, userLon, diagnostiqueurLat, diagnostiqueurLon);
            distanceText = `${distance} km`;
        }
        
        const prixTotal = prixBase + parseFloat(data.visiting_charges);

        card.innerHTML = `
            <img src="${data.image}" alt="Photo de profil" class="profile-pic">
            <div class="diagnostiqueur-info">
                <p class="diagnostiqueur-name">${data.company_name}</p>
                <div class="diagnostiqueur-rating">
                    ${'★'.repeat(Math.round(data.ratings))}${'☆'.repeat(5 - Math.round(data.ratings))} (${data.ratings})
                </div>
                <div class="diagnostiqueur-details">
                    <p>Prix total: ${prixTotal}€</p>
                    <p>Distance: ${distanceText}</p>
                </div>
            </div>
        `;

        card.addEventListener('click', () => {
            document.querySelectorAll('.diagnostiqueur-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            
            localStorage.setItem('selectedDiagnostiqueur', JSON.stringify(data));
            localStorage.setItem('totalPrice', prixTotal);
            
            continueBtn.disabled = false;
        });

        return card;
    }

    async function fetchDiagnostiqueurs() {
        diagnostiqueursContainer.innerHTML = `<p>Chargement des diagnostiqueurs...</p>`;
        const url = 'https://hubhabitats.ekkleo.com/api/v1/get_providers';
        const body = {
            latitude: userLat,
            longitude: userLon,
            limit: 200000 
        };

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.error || !result.data || result.data.length === 0) {
                diagnostiqueursContainer.innerHTML = `<p>Aucun diagnostiqueur trouvé dans votre région.</p>`;
            } else {
                diagnostiqueursContainer.innerHTML = ''; 
                result.data.forEach(diagnostiqueur => {
                    const card = createDiagnostiqueurCard(diagnostiqueur);
                    diagnostiqueursContainer.appendChild(card);
                });
            }

        } catch (error) {
            console.error('Erreur lors de la récupération des diagnostiqueurs:', error);
            diagnostiqueursContainer.innerHTML = `<p style="color: red;">Une erreur est survenue. Veuillez réessayer plus tard.</p>`;
        }
    }

    continueBtn.addEventListener('click', () => {
        const selectedDiagnostiqueur = localStorage.getItem('selectedDiagnostiqueur');
        if (selectedDiagnostiqueur) {
            window.location.href = 'services.html'; 
        } else {
            alert('Veuillez sélectionner un diagnostiqueur pour continuer.');
        }
    });

    backBtn.addEventListener('click', () => {
        window.history.back();
    });

    fetchDiagnostiqueurs();
});