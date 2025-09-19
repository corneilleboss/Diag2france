document.addEventListener('DOMContentLoaded', () => {
    // Éléments de la page
    const diagnostiqueurName = document.getElementById('diagnostiqueur-name');
    const distanceInfo = document.getElementById('distance-info');
    const dureeInfo = document.getElementById('duree-info');
    const diagnosticsList = document.getElementById('diagnostics-list');
    const servicesList = document.getElementById('services-list');
    const prixTotalInfo = document.getElementById('prix-total-info');
    const backBtn = document.querySelector('.back-btn');
    const nextBtn = document.querySelector('.next-btn');
    const appointmentDateInput = document.getElementById('appointment-date');
    const timeSlotsContainer = document.getElementById('time-slots-container');

    // Nouveaux éléments pour la gestion du client
    const clientTypeRadios = document.querySelectorAll('input[name="client-type"]');
    const particulierFields = document.getElementById('particulier-fields');
    const professionnelFields = document.getElementById('professionnel-fields');

    // Nouveaux éléments pour la gestion de l'adresse de facturation
    const sameAddressRadios = document.querySelectorAll('input[name="same-address"]');
    const differentAddressFields = document.getElementById('different-billing-address-fields');
    const billingAddressInput = document.getElementById('billing-address');
    const billingCityInput = document.getElementById('billing-city');
    const billingZipInput = document.getElementById('billing-zip');
    
    // Éléments de la pop-up
    const paymentPopup = document.getElementById('payment-popup');
    const paymentOptionBtns = document.querySelectorAll('.payment-option-btn');
    const nextPopupBtn = document.querySelector('.next-step-popup-btn');

    // Récupération des données du localStorage
    const selectedDiagnostiqueur = JSON.parse(localStorage.getItem('selectedDiagnostiqueur'));
    const checkedDiagnosticsCount = parseInt(localStorage.getItem('checkedDiagnosticsCount'));
    const selectedServices = JSON.parse(localStorage.getItem('selectedServices')) || [];
    const totalPrice = localStorage.getItem('totalPrice');
    const propertyAddress = localStorage.getItem('fullAddress');
    const propertyCity = localStorage.getItem('city');
    const propertyZip = localStorage.getItem('zipCode');

    let selectedTime = null;

    // Vérification initiale des données essentielles
    if (!selectedDiagnostiqueur || !selectedDiagnostiqueur.partner_id || isNaN(checkedDiagnosticsCount)) {
        document.querySelector('.container').innerHTML = `<p style="color: red;">Erreur: Les informations du diagnostiqueur sont manquantes ou incorrectes. Veuillez retourner à l'étape précédente et en sélectionner un à nouveau.</p>`;
        nextBtn.disabled = true;
        return;
    }

    // Gestion de l'affichage des champs client
    clientTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'particulier') {
                particulierFields.style.display = 'block';
                professionnelFields.style.display = 'none';
            } else {
                particulierFields.style.display = 'none';
                professionnelFields.style.display = 'block';
            }
        });
    });

    // Gestion de l'affichage des champs d'adresse de facturation
    sameAddressRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'no') {
                differentAddressFields.style.display = 'block';
            } else {
                differentAddressFields.style.display = 'none';
            }
        });
    });

    // Récupération et affichage des diagnostics obligatoires
    const anneeConstruction = localStorage.getItem('anneeConstruction');
    const gazInstallation = localStorage.getItem('gazInstallation');
    const elecInstallation = localStorage.getItem('elecInstallation');
    
    let mandatoryDiagnostics = [];
    if (anneeConstruction === 'avant-1949') {
        mandatoryDiagnostics = ['DPE', 'AMIANTE', 'PLOMB', 'CARREZE BOUTIN', 'ERP'];
    } else if (anneeConstruction === 'apres-1949') {
        mandatoryDiagnostics = ['DPE', 'AMIANTE', 'ERP', 'THERMITES', 'CARREZE BOUTIN'];
    } else if (anneeConstruction === 'apres-1997') {
        mandatoryDiagnostics = ['DPE', 'ERP', 'THERMITES', 'CARREZE BOUTIN'];
    }
    if (gazInstallation === 'plus-15ans') {
        mandatoryDiagnostics.push('GAZ');
    }
    if (elecInstallation === 'plus-15ans') {
        mandatoryDiagnostics.push('ELECTRICITE');
    }
    mandatoryDiagnostics = mandatoryDiagnostics.slice(0, checkedDiagnosticsCount);

    // Afficher les détails de la mission
    diagnostiqueurName.textContent = selectedDiagnostiqueur.company_name;

    // Calcul et affichage de la distance et du temps de trajet
    const userLat = parseFloat(localStorage.getItem('latitude'));
    const userLon = parseFloat(localStorage.getItem('longitude'));
    const diagnostiqueurLat = parseFloat(selectedDiagnostiqueur.latitude);
    const diagnostiqueurLon = parseFloat(selectedDiagnostiqueur.longitude);
    
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
    
    function getTravelTime(distanceKm) {
        const averageSpeedKmh = 40;
        if (distanceKm === 0) return "< 1 min";
        const travelTimeHours = distanceKm / averageSpeedKmh;
        const travelTimeMinutes = Math.round(travelTimeHours * 60);
        return `${travelTimeMinutes} min`;
    }
    
    const distanceKm = calculateDistance(userLat, userLon, diagnostiqueurLat, diagnostiqueurLon);
    distanceInfo.textContent = `${distanceKm} km`;
    dureeInfo.textContent = getTravelTime(distanceKm);

    // Affichage de la liste des diagnostics et services
    mandatoryDiagnostics.forEach(diag => {
        const li = document.createElement('li');
        li.textContent = diag;
        diagnosticsList.appendChild(li);
    });

    if (selectedServices.length > 0) {
        selectedServices.forEach(service => {
            const li = document.createElement('li');
            li.textContent = `${service.name} (${service.price}€)`;
            servicesList.appendChild(li);
        });
    } else {
        servicesList.innerHTML = '<li>Aucun service additionnel sélectionné.</li>';
    }

    prixTotalInfo.textContent = `${totalPrice} €`;

    // Fonction pour afficher des créneaux horaires statiques
    function displayStaticSlots() {
        const timeSlots = ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'];
        timeSlotsContainer.innerHTML = '';
        timeSlots.forEach(slot => {
            const timeBtn = document.createElement('button');
            timeBtn.className = 'time-slot-btn';
            timeBtn.textContent = slot;
            timeBtn.addEventListener('click', () => {
                document.querySelectorAll('.time-slot-btn').forEach(btn => btn.classList.remove('selected'));
                timeBtn.classList.add('selected');
                selectedTime = slot;
                nextBtn.disabled = false;
            });
            timeSlotsContainer.appendChild(timeBtn);
        });
    }

    // Gestion de la sélection de la date
    appointmentDateInput.addEventListener('change', (event) => {
        const selectedDate = event.target.value;
        if (selectedDate) {
            displayStaticSlots();
            selectedTime = null;
            nextBtn.disabled = true;
        }
    });
    
    // Gestion du bouton de retour
    backBtn.addEventListener('click', () => {
        window.history.back();
    });

    // Fonction de validation des champs
    function validateForm() {
        const clientType = document.querySelector('input[name="client-type"]:checked').value;
        const date = appointmentDateInput.value;
        const billingAddressIsSame = document.querySelector('input[name="same-address"]:checked').value === 'yes';

        if (!date || !selectedTime) {
            return false;
        }

        if (clientType === 'particulier') {
            const name = document.getElementById('client-name').value;
            const surname = document.getElementById('client-surname').value;
            const email = document.getElementById('client-email').value;
            const phone = document.getElementById('client-phone').value;
            const lot = document.getElementById('client-lot').value;
            if (!name || !surname || !email || !phone || !lot) return false;
        } else { // professionnel
            const repName = document.getElementById('pro-rep-name').value;
            const phone = document.getElementById('pro-phone').value;
            const address = document.getElementById('pro-address').value;
            const form = document.getElementById('pro-form').value;
            const quality = document.getElementById('pro-quality').value;
            const email = document.getElementById('pro-email').value;
            const siret = document.getElementById('pro-siret').value;
            const tva = document.getElementById('pro-tva').value;
            const lot = document.getElementById('pro-numlot').value;
            if (!repName || !phone || !address || !form || !quality || !email || !siret || !tva) return false;
        }
        
        if (!billingAddressIsSame) {
            const billingAddress = billingAddressInput.value;
            const billingCity = billingCityInput.value;
            const billingZip = billingZipInput.value;
            if (!billingAddress || !billingCity || !billingZip) return false;
        }

        return true;
    }

    // Gestion du bouton 'Suivant' de la page principale (affiche la pop-up)
    nextBtn.addEventListener('click', () => {
        if (!validateForm()) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return;
        }
        paymentPopup.classList.add('active');
    });

    // Gestion des événements de la pop-up
    paymentOptionBtns.forEach(button => {
        button.addEventListener('click', (event) => {
            paymentOptionBtns.forEach(btn => btn.classList.remove('selected'));
            event.target.classList.add('selected');
            nextPopupBtn.disabled = false;
        });
    });

    // Gestion du bouton 'Suivant' de la pop-up
    nextPopupBtn.addEventListener('click', () => {
        const selectedOption = document.querySelector('.payment-option-btn.selected').dataset.option;
        localStorage.setItem('paymentOption', selectedOption);

        const clientType = document.querySelector('input[name="client-type"]:checked').value;
        let clientInfo = {};

        if (clientType === 'particulier') {
            clientInfo = {
                type: 'particulier',
                nom: document.getElementById('client-name').value,
                prenom: document.getElementById('client-surname').value,
                email: document.getElementById('client-email').value,
                telephone: document.getElementById('client-phone').value
            };
        } else {
            clientInfo = {
                type: 'professionnel',
                representantLegal: document.getElementById('pro-rep-name').value,
                telephone: document.getElementById('pro-phone').value,
                adresseSiege: document.getElementById('pro-address').value,
                forme: document.getElementById('pro-form').value,
                qualite: document.getElementById('pro-quality').value,
                email: document.getElementById('pro-email').value,
                siret: document.getElementById('pro-siret').value,
                tva: document.getElementById('pro-tva').value
            };
        }
        
        // Récupération de l'adresse du bien depuis le localStorage
        const propertyAddress = localStorage.getItem('fullAddress');
        
        let billingAddressData;
        const isSameAddress = document.querySelector('input[name="same-address"]:checked').value === 'yes';
        if (isSameAddress) {
            billingAddressData = {
                adresse: propertyAddress,
                ville: propertyCity,
                codePostal: propertyZip,
                isSame: true
            };
        } else {
            billingAddressData = {
                adresse: billingAddressInput.value,
                ville: billingCityInput.value,
                codePostal: billingZipInput.value,
                isSame: false
            };
        }

        const rendezVousInfo = {
            date: appointmentDateInput.value,
            heure: selectedTime,
            client: clientInfo,
            adresseFacturation: billingAddressData,
            adresseBien: propertyAddress,
            diagnostiqueur: {
                nom: selectedDiagnostiqueur.company_name,
                email: selectedDiagnostiqueur.email,
            },
            diagnostics: mandatoryDiagnostics,
            services: selectedServices,
            prixTotal: totalPrice,
        };
        
        localStorage.setItem('rendezVousInfo', JSON.stringify(rendezVousInfo));

        if (selectedOption === 'agent') {
            const backendEndpoint = 'create_google_calendar_event.php';
            fetch(backendEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rendezVousInfo),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('L\'événement a été ajouté à Google Agenda avec succès !');
                    window.location.href = 'confirmation-agenda.html';
                } else {
                    alert('Erreur lors de la création de l\'événement : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur de connexion au serveur:', error);
                alert('Erreur: Impossible de créer l\'événement. Veuillez réessayer.');
            });
        } else if (selectedOption === 'client') {
            const backendEndpoint = 'generate-quote.php';
            fetch(backendEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rendezVousInfo),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Le lien du devis a été envoyé au client avec succès !');
                    window.location.href = 'confirmation-quote.html';
                } else {
                    alert('Erreur lors de la génération du devis : ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur de connexion au serveur:', error);
                alert('Erreur: Impossible de générer le devis. Veuillez réessayer.');
            });
        }
    });

    // Affichage des infos client dans appointment.html
    function displayClientInfo() {
        const clientType = localStorage.getItem('client-type');
        if (clientType === 'particulier') {
            document.getElementById('client-name').value = localStorage.getItem('client-name') || '';
            document.getElementById('client-surname').value = localStorage.getItem('client-surname') || '';
            document.getElementById('client-email').value = localStorage.getItem('client-email') || '';
            document.getElementById('client-phone').value = localStorage.getItem('client-phone') || '';
            document.getElementById('client-lot').value = localStorage.getItem('client-lot') || '';
            document.querySelector('input[value="particulier"]').checked = true;
            particulierFields.style.display = 'block';
            professionnelFields.style.display = 'none';
        } else if (clientType === 'professionnel') {
            document.getElementById('pro-rep-name').value = localStorage.getItem('pro-rep-name') || '';
            document.getElementById('pro-phone').value = localStorage.getItem('pro-phone') || '';
            document.getElementById('pro-address').value = localStorage.getItem('pro-address') || '';
            document.getElementById('pro-form').value = localStorage.getItem('pro-form') || '';
            document.getElementById('pro-quality').value = localStorage.getItem('pro-quality') || '';
            document.getElementById('pro-email').value = localStorage.getItem('pro-email') || '';
            document.getElementById('pro-siret').value = localStorage.getItem('pro-siret') || '';
            document.getElementById('pro-tva').value = localStorage.getItem('pro-tva') || '';
            document.getElementById('pro-numlot').value = localStorage.getItem('pro-numlot') || '';
            document.querySelector('input[value="professionnel"]').checked = true;
            particulierFields.style.display = 'none';
            professionnelFields.style.display = 'block';
        }
    }

    // Appelle la fonction pour afficher les infos client au chargement de la page
    displayClientInfo();
});